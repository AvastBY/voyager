<?php

namespace TCG\Voyager\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TCG\Voyager\Database\DatabaseUpdater;
use TCG\Voyager\Database\Schema\Column;
use TCG\Voyager\Database\Schema\Identifier;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Database\Schema\Table;
use TCG\Voyager\Database\Types\Type;
use TCG\Voyager\Events\TableAdded;
use TCG\Voyager\Events\TableDeleted;
use TCG\Voyager\Events\TableUpdated;
use TCG\Voyager\Facades\Voyager;

class VoyagerDatabaseController extends Controller
{
    public function index()
    {
        $this->authorize('browse_database');

        $dataTypes = Voyager::model('DataType')->select('id', 'name', 'slug')->get()->keyBy('name')->toArray();

        $tables = array_map(function ($table) use ($dataTypes) {
            $table = Str::replaceFirst(DB::getTablePrefix(), '', $table);

            $table = [
                'prefix'     => DB::getTablePrefix(),
                'name'       => $table,
                'slug'       => $dataTypes[$table]['slug'] ?? null,
                'dataTypeId' => $dataTypes[$table]['id'] ?? null,
            ];

            return (object) $table;
        }, SchemaManager::listTableNames());

        return Voyager::view('voyager::tools.database.index')->with(compact('dataTypes', 'tables'));
    }

    /**
     * Create database table.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function create()
    {
        $this->authorize('browse_database');

        $db = $this->prepareDbManager('create');

        return Voyager::view('voyager::tools.database.edit-add', compact('db'));
    }

    /**
     * Store new database table.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->authorize('browse_database');

        try {
            $conn = 'database.connections.'.config('database.default');
            Type::registerCustomPlatformTypes();

            $table = $request->table;
            if (!is_array($request->table)) {
                $table = json_decode($request->table, true);
            }

            // Log the input data for debugging
            \Log::debug('Table data:', ['table' => $table]);

            // Ensure required fields are present
            if (!isset($table['name'])) {
                throw new \Exception('Table name is required');
            }

            if (!isset($table['columns']) || !is_array($table['columns'])) {
                throw new \Exception('Table must have at least one column');
            }

            // Validate each column
            foreach ($table['columns'] as $columnArr) {
                if (!isset($columnArr['name'])) {
                    throw new \Exception('Column name is required');
                }
                if (!isset($columnArr['type'])) {
                    throw new \Exception('Column type is required for column '.$columnArr['name']);
                }
                // Log column data for debugging
                \Log::debug('Column data:', ['column' => $columnArr]);
            }

            // Set default values for optional fields
            $table['indexes'] = $table['indexes'] ?? [];
            $table['foreignKeys'] = $table['foreignKeys'] ?? [];
            $table['options'] = $table['options'] ?? [];

            // Set collation and charset
            $table['options']['collate'] = config($conn.'.collation', 'utf8mb4_unicode_ci');
            $table['options']['charset'] = config($conn.'.charset', 'utf8mb4');

            try {
                // Create table object
                $table = Table::make($table);
                // Log table object for debugging
                \Log::debug('Table object:', ['table' => $table->toArray()]);
            } catch (\Exception $e) {
                throw new \Exception('Error creating table object: '.$e->getMessage());
            }

            try {
                // Save table
                SchemaManager::createTable($table);
            } catch (\Exception $e) {
                throw new \Exception('Error saving table to database: '.$e->getMessage()."\nTable: ".print_r($table->toArray(), true));
            }

            if (isset($request->create_model) && $request->create_model == 'on') {
                try {
                    $modelNamespace = config('voyager.models.namespace', app()->getNamespace());
                    $params = [
                        'name' => $modelNamespace.Str::studly(Str::singular($table->name)),
                    ];

                    // Check if table has deleted_at column
                    $hasDeletedAt = false;
                    foreach ($table->getColumns() as $column) {
                        if ($column->getName() === 'deleted_at') {
                            $hasDeletedAt = true;
                            break;
                        }
                    }

                    if ($hasDeletedAt) {
                        $params['--softdelete'] = true;
                    }

                    if (isset($request->create_migration) && $request->create_migration == 'on') {
                        $params['--migration'] = true;
                    }

                    Artisan::call('voyager:make:model', $params);
                } catch (\Exception $e) {
                    throw new \Exception('Error creating model: '.$e->getMessage());
                }
            } elseif (isset($request->create_migration) && $request->create_migration == 'on') {
                try {
                    Artisan::call('make:migration', [
                        'name'    => 'create_'.$table->name.'_table',
                        '--table' => $table->name,
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception('Error creating migration: '.$e->getMessage());
                }
            }

            event(new TableAdded($table));

            return redirect()
               ->route('voyager.database.index')
               ->with($this->alertSuccess(__('voyager::database.success_create_table', ['table' => $table->name])));
        } catch (Exception $e) {
            \Log::error('Error in store method: '.$e->getMessage(), [
                'exception' => $e,
                'table' => isset($table) ? (is_object($table) ? $table->toArray() : $table) : null
            ]);
            return back()->with($this->alertException($e))->withInput();
        }
    }

    /**
     * Edit database table.
     *
     * @param string $table
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function edit($table)
    {
        $this->authorize('browse_database');

        if (!SchemaManager::tableExists($table)) {
            return redirect()
                ->route('voyager.database.index')
                ->with($this->alertError(__('voyager::database.edit_table_not_exist')));
        }

        $db = $this->prepareDbManager('update', $table);

        return Voyager::view('voyager::tools.database.edit-add', compact('db'));
    }

    /**
     * Update database table.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $this->authorize('browse_database');

        $table = json_decode($request->table, true);

        try {
            DatabaseUpdater::update($table);
            // TODO: synch BREAD with Table
            // $this->cleanOldAndCreateNew($request->original_name, $request->name);
            event(new TableUpdated($table));
        } catch (Exception $e) {
            return back()->with($this->alertException($e))->withInput();
        }

        return redirect()
               ->route('voyager.database.index')
               ->with($this->alertSuccess(__('voyager::database.success_create_table', ['table' => $table['name']])));
    }

    protected function prepareDbManager($action, $table = '')
    {
        $db = new \stdClass();

        // For Laravel, we don't need platform types like in DBAL
        $db->types = Type::getPlatformTypes();

        if ($action == 'update') {
            $db->table = SchemaManager::listTableDetails($table);
            $db->formAction = route('voyager.database.update', $table);
        } else {
            $db->table = new Table('New Table');

            // Add prefilled columns
            $db->table->addColumn('id', 'integer', [
                'unsigned'      => true,
                'notnull'       => true,
                'autoincrement' => true,
            ]);

            $db->table->setPrimaryKey(['id'], 'primary');

            $db->formAction = route('voyager.database.store');
        }

        $oldTable = old('table');
        $db->oldTable = $oldTable ? $oldTable : json_encode(null);
        $db->action = $action;
        $db->identifierRegex = $this->getIdentifierRegex();
        $db->platform = 'mysql'; // Since we're using MySQL directly

        return $db;
    }
    
    protected function getColumnTypes()
    {
    	return json_decode('{"Numbers":[{"name":"bigint","category":"Numbers","default":{"type":"number","step":"any"}},{"name":"decimal","category":"Numbers","default":{"type":"number","step":"any"}},{"name":"double","category":"Numbers","default":{"type":"number","step":"any"}},{"name":"float","category":"Numbers","default":{"type":"number","step":"any"}},{"name":"integer","category":"Numbers","default":{"type":"number","step":"any"}},{"name":"mediumint","category":"Numbers","default":{"type":"number","step":"any"}},{"name":"smallint","category":"Numbers","default":{"type":"number","step":"any"}},{"name":"tinyint","category":"Numbers","default":{"type":"number","step":"any"}}],"Binary":[{"name":"binary","category":"Binary"},{"name":"blob","category":"Binary","notSupportIndex":true,"default":{"disabled":true}},{"name":"longblob","category":"Binary","notSupportIndex":true,"default":{"disabled":true}},{"name":"mediumblob","category":"Binary","notSupportIndex":true,"default":{"disabled":true}},{"name":"tinyblob","category":"Binary","notSupportIndex":true,"default":{"disabled":true}},{"name":"varbinary","category":"Binary"},{"name":"bit","category":"Binary"}],"Strings":[{"name":"char","category":"Strings"},{"name":"longtext","category":"Strings","notSupportIndex":true,"default":{"disabled":true}},{"name":"mediumtext","category":"Strings","notSupportIndex":true,"default":{"disabled":true}},{"name":"text","category":"Strings","notSupportIndex":true,"default":{"disabled":true}},{"name":"tinytext","category":"Strings","notSupportIndex":true,"default":{"disabled":true}},{"name":"varchar","category":"Strings"}],"Date and Time":[{"name":"date","category":"Date and Time","default":{"type":"date"}},{"name":"datetime","category":"Date and Time"},{"name":"time","category":"Date and Time","default":{"type":"time","step":"1"}},{"name":"timestamp","category":"Date and Time"},{"name":"year","category":"Date and Time","default":{"type":"number","min":"0"}}],"Lists":[{"name":"set","category":"Lists","notSupported":true},{"name":"json","category":"Lists","default":{"disabled":true}},{"name":"enum","category":"Lists","notSupported":true}],"Geometry":[{"name":"geometrycollection","category":"Geometry"},{"name":"geometry","category":"Geometry"},{"name":"linestring","category":"Geometry"},{"name":"multilinestring","category":"Geometry"},{"name":"multipoint","category":"Geometry"},{"name":"multipolygon","category":"Geometry"},{"name":"point","category":"Geometry"},{"name":"polygon","category":"Geometry"}]}');
        return [
            'integer' => 'Integer',
            'bigint' => 'Big Integer',
            'varchar' => 'String',
            'text' => 'Text',
            'date' => 'Date',
            'datetime' => 'DateTime',
            'time' => 'Time',
            'timestamp' => 'Timestamp',
            'float' => 'Float',
            'double' => 'Double',
            'decimal' => 'Decimal',
            'boolean' => 'Boolean',
            'json' => 'JSON',
            'enum' => 'Enum',
        ];
    }

    protected function listTableDetails($tableName)
    {
        $columns = DB::select("SHOW COLUMNS FROM `{$tableName}`");
        $indexes = DB::select("SHOW INDEXES FROM `{$tableName}`");

        $table = new Table($tableName);

        foreach ($columns as $column) {
            $type = $this->normalizeType($column->Type);
            $options = [
                'notnull' => $column->Null !== 'YES',
                'default' => $column->Default,
            ];

            if ($column->Extra === 'auto_increment') {
                $options['autoincrement'] = true;
            }

            if (str_contains($column->Type, 'unsigned')) {
                $options['unsigned'] = true;
            }

            // Extract length from type if exists (e.g., varchar(255))
            if (preg_match('/\((\d+)\)/', $column->Type, $matches)) {
                $options['length'] = (int) $matches[1];
            }

            $table->addColumn($column->Field, $type, $options);

            if ($column->Key === 'PRI') {
                $table->setPrimaryKey([$column->Field]);
            }
        }

        foreach ($indexes as $index) {
            if ($index->Key_name !== 'PRIMARY') {
                if (!isset($table->_indexes[$index->Key_name])) {
                    $table->_indexes[$index->Key_name] = [];
                }
                $table->_indexes[$index->Key_name][] = $index->Column_name;
            }
        }

        return $table;
    }

    protected function normalizeType($type)
    {
        if (str_contains($type, 'int')) return 'integer';
        if (str_contains($type, 'varchar')) return 'string';
        if (str_contains($type, 'text')) return 'text';
        if (str_contains($type, 'datetime')) return 'datetime';
        if (str_contains($type, 'timestamp')) return 'timestamp';
        if (str_contains($type, 'time')) return 'time';
        if (str_contains($type, 'date')) return 'date';
        if (str_contains($type, 'float')) return 'float';
        if (str_contains($type, 'double')) return 'double';
        if (str_contains($type, 'decimal')) return 'decimal';
        if (str_contains($type, 'json')) return 'json';
        if (str_contains($type, 'enum')) return 'enum';
        if (str_contains($type, 'bool')) return 'boolean';
        
        return $type;
    }

    protected function createNewTableStructure()
    {
        return [
            'name' => 'New Table',
            'columns' => [
                [
                    'name' => 'id',
                    'type' => 'integer',
                    'unsigned' => true,
                    'notnull' => true,
                    'autoincrement' => true,
                ]
            ],
            'primaryKey' => 'id',
            'indexes' => [],
        ];
    }

    protected function getIdentifierRegex()
    {
        return '[a-zA-Z_][a-zA-Z0-9_]*';
    }
    
    public function cleanOldAndCreateNew($originalName, $tableName)
    {
        if (!empty($originalName) && $originalName != $tableName) {
            $dt = DB::table('data_types')->where('name', $originalName);
            if ($dt->get()) {
                $dt->delete();
            }

            $perm = DB::table('permissions')->where('table_name', $originalName);
            if ($perm->get()) {
                $perm->delete();
            }

            $params = ['name' => Str::studly(Str::singular($tableName))];
            Artisan::call('voyager:make:model', $params);
        }
    }

    public function reorder_column(Request $request)
    {
        $this->authorize('browse_database');

        if ($request->ajax()) {
            $table = $request->table;
            $column = $request->column;
            $after = $request->after;
            if ($after == null) {
                // SET COLUMN TO THE TOP
                DB::query("ALTER $table MyTable CHANGE COLUMN $column FIRST");
            }

            return 1;
        }

        return 0;
    }

    /**
     * Show table.
     *
     * @param string $table
     *
     * @return JSON
     */
    public function show($table)
    {
        $this->authorize('browse_database');

        $additional_attributes = [];
        $model_name = Voyager::model('DataType')->where('name', $table)->pluck('model_name')->first();
        if (isset($model_name)) {
            $model = app($model_name);
            if (isset($model->additional_attributes)) {
                foreach ($model->additional_attributes as $attribute) {
                    $additional_attributes[$attribute] = [];
                }
            }
        }
        
        return response()->json(collect(SchemaManager::describeTable($table))->merge($additional_attributes));
    }

    /**
     * Destroy table.
     *
     * @param string $table
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($table)
    {
        $this->authorize('browse_database');

        try {
            SchemaManager::dropTable($table);
            event(new TableDeleted($table));

            return redirect()
                ->route('voyager.database.index')
                ->with($this->alertSuccess(__('voyager::database.success_delete_table', ['table' => $table])));
        } catch (Exception $e) {
            return back()->with($this->alertException($e));
        }
    }
}
