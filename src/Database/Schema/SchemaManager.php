<?php

namespace TCG\Voyager\Database\Schema;

use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table as DoctrineTable;
use Doctrine\DBAL\Schema\MySQLSchemaManager;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\DriverManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TCG\Voyager\Database\Types\Type;

abstract class SchemaManager
{
    protected static $platform;
    protected static $connection;
    
    // todo: trim parameters

    public static function __callStatic($method, $args)
    {
        return static::connection()->$method(...$args);
    }

    public static function connection()
    {
        return DB::connection();
    }

    public static function schemaBuilder()
    {
        return Schema::connection(static::connection()->getName());
    }

    public static function getDatabaseConnection()
    {
        if (!static::$connection) {
            $connection = DB::connection();
            if (method_exists($connection, 'getDoctrineConnection')) {
                static::$connection = $connection->getDoctrineConnection();
            } else {
                // For Laravel 12+
                $config = $connection->getConfig();
                
                $params = [
                    'dbname' => $config['database'],
                    'user' => $config['username'],
                    'password' => $config['password'],
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'driver' => 'pdo_mysql',
                    'charset' => $config['charset'] ?? 'utf8mb4',
                ];

                // If using a socket connection
                if (isset($config['unix_socket'])) {
                    $params['unix_socket'] = $config['unix_socket'];
                }

                try {
                    static::$connection = DriverManager::getConnection($params);
                } catch (\Exception $e) {
                    // If direct connection fails, try using existing PDO connection
                    $params = [
                        'pdo' => $connection->getPdo(),
                        'dbname' => $config['database'],
                        'driver' => 'pdo_mysql',
                        'charset' => $config['charset'] ?? 'utf8mb4'
                    ];
                    static::$connection = DriverManager::getConnection($params);
                }
            }
        }
        return static::$connection;
    }

    public static function getDatabasePlatform()
    {
        if (!static::$platform) {
            $connection = static::getDatabaseConnection();
            static::$platform = $connection->getDatabasePlatform();
        }
        return static::$platform;
    }

    public static function manager()
    {
        return static::getDatabaseConnection()->createSchemaManager();
    }

    public static function tableExists($table)
    {
        if (!is_array($table)) {
            $table = [$table];
        }

        return static::manager()->tablesExist($table);
    }

	public static function listTableNames(){
		try {
			return static::manager()->listTableNames();
		} catch (\Exception $e) {
			// Fallback for some database types that might not support direct table listing
			$query = static::connection()->query()->select('table_name as name')
				->from('information_schema.tables')
				->where('table_schema', static::connection()->getDatabaseName())
				->where('table_type', 'BASE TABLE');
			return $query->pluck('name')->toArray();
		}
	}
    
    public static function listTables()
    {
        $tables = [];

        foreach (static::listTableNames() as $tableName) {
            try {
                $tables[$tableName] = static::listTableDetails($tableName);
            } catch (\Exception $e) {
                // Skip tables that can't be introspected
                continue;
            }
        }

        return $tables;
    }

    /**
     * @param string $tableName
     *
     * @return \TCG\Voyager\Database\Schema\Table
     */
    public static function listTableDetails($tableName)
    {
        try {
            $columns = static::manager()->listTableColumns($tableName);

            $foreignKeys = [];
            if (!static::getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\SQLitePlatform) {
                // All modern MySQL/MariaDB versions support foreign keys
                $foreignKeys = static::manager()->listTableForeignKeys($tableName);
            }

            $indexes = static::manager()->listTableIndexes($tableName);

            return new Table($tableName, $columns, $indexes, [], $foreignKeys, []);
        } catch (\Exception $e) {
            throw new \Exception("Error listing table details for '$tableName': " . $e->getMessage());
        }
    }

    /**
     * Describes given table.
     *
     * @param string $tableName
     *
     * @return \Illuminate\Support\Collection
     */
    public static function describeTable($tableName)
    {
        Type::registerCustomPlatformTypes();

        try {
            $table = static::listTableDetails($tableName);

            return collect($table->columns)->map(function ($column) use ($table) {
                $columnArr = Column::toArray($column);

                $columnArr['field'] = $columnArr['name'];
                $type = $column->getType();
                $columnArr['type'] = $type->getTypeRegistry()->lookupName($type);

                // Set the indexes and key
                $columnArr['indexes'] = [];
                $columnArr['key'] = null;
                if ($columnArr['indexes'] = $table->getColumnsIndexes($columnArr['name'], true)) {
                    // Convert indexes to Array
                    foreach ($columnArr['indexes'] as $name => $index) {
                        $columnArr['indexes'][$name] = Index::toArray($index);
                    }

                    // If there are multiple indexes for the column
                    // the Key will be one with highest priority
                    $indexType = array_values($columnArr['indexes'])[0]['type'];
                    $columnArr['key'] = substr($indexType, 0, 3);
                }

                return $columnArr;
            })->keyBy('name');
        } catch (\Exception $e) {
            throw new \Exception("Error describing table '$tableName': " . $e->getMessage());
        }
    }

    public static function listTableColumnNames($tableName)
    {
        Type::registerCustomPlatformTypes();

        try {
            $columnNames = [];

            foreach (static::manager()->listTableColumns($tableName) as $column) {
                $columnNames[] = $column->getName();
            }

            return $columnNames;
        } catch (\Exception $e) {
            throw new \Exception("Error listing column names for table '$tableName': " . $e->getMessage());
        }
    }

    public static function createTable($table)
    {
        if (!($table instanceof DoctrineTable)) {
            try {
                $table = Table::make($table);
            } catch (\Exception $e) {
                throw new \Exception('Error creating table object: '.$e->getMessage());
            }
        }

        try {
            // Log table details for debugging
            \Log::debug('Creating table:', [
                'name' => $table->getName(),
                'columns' => array_map(function($column) {
                    $type = $column->getType();
                    return [
                        'name' => $column->getName(),
                        'type' => $type->getTypeRegistry()->lookupName($type),
                        'options' => $column->toArray()
                    ];
                }, $table->getColumns()),
                'indexes' => array_map(function($index) {
                    return [
                        'name' => $index->getName(),
                        'columns' => $index->getColumns(),
                        'isPrimary' => $index->isPrimary(),
                        'isUnique' => $index->isUnique()
                    ];
                }, $table->getIndexes()),
                'foreignKeys' => array_map(function($fk) {
                    return [
                        'name' => $fk->getName(),
                        'localColumns' => $fk->getLocalColumns(),
                        'foreignTable' => $fk->getForeignTableName(),
                        'foreignColumns' => $fk->getForeignColumns()
                    ];
                }, $table->getForeignKeys()),
                'options' => $table->getOptions()
            ]);

            static::manager()->createTable($table);
        } catch (\Exception $e) {
            \Log::error('Error creating table: '.$e->getMessage(), [
                'exception' => $e,
                'table' => $table instanceof DoctrineTable ? [
                    'name' => $table->getName(),
                    'columns' => array_map(function($column) {
                        $type = $column->getType();
                        return [
                            'name' => $column->getName(),
                            'type' => $type->getTypeRegistry()->lookupName($type),
                            'options' => $column->toArray()
                        ];
                    }, $table->getColumns())
                ] : $table
            ]);
            throw new \Exception('Error creating table: '.$e->getMessage());
        }
    }

    public static function getDoctrineTable($table)
    {
        $table = trim($table);

        if (!static::tableExists($table)) {
            throw SchemaException::tableDoesNotExist($table);
        }

        try {
            return static::manager()->introspectTable($table);
        } catch (\Exception $e) {
            throw new \Exception("Error getting Doctrine table for '$table': " . $e->getMessage());
        }
    }

    public static function getDoctrineColumn($table, $column)
    {
        try {
            return static::getDoctrineTable($table)->getColumn($column);
        } catch (\Exception $e) {
            throw new \Exception("Error getting column '$column' from table '$table': " . $e->getMessage());
        }
    }

    public static function alterTable($tableDiff)
    {
        try {
            $platform = static::getDatabasePlatform();
            $connection = static::connection();

            // Get SQL statements from TableDiff
            $sql = $platform->getAlterTableSQL($tableDiff);

            // Start transaction
            \DB::beginTransaction();

            try {
                // Execute each SQL statement
                foreach ($sql as $query) {
                    $connection->unprepared($query);
                }

                // If we got here, commit the transaction
                \DB::commit();
            } catch (\Exception $e) {
                // Something went wrong, try to rollback if transaction is still active
                try {
                    if (\DB::transactionLevel() > 0) {
                        \DB::rollBack();
                    }
                } catch (\Exception $rollbackException) {
                    // Ignore rollback errors, as the original error is more important
                }
                throw $e;
            }
        } catch (\Exception $e) {
            // Ignore "no active transaction" errors as they're not critical
            if (strpos($e->getMessage(), 'no active transaction') === false) {
                throw new \Exception("Error altering table: " . $e->getMessage());
            }
        }
    }
}
