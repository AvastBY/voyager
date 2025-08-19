<?php

namespace TCG\Voyager\Database\Schema;

use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Table as DoctrineTable;

class Table extends DoctrineTable
{
    public static function make($table)
    {
        if (!is_array($table)) {
            $table = json_decode($table, true);
        }

        if (!isset($table['name'])) {
            throw new \Exception('Table name is required');
        }

        $name = Identifier::validate($table['name'], 'Table');

        if (!isset($table['columns']) || !is_array($table['columns'])) {
            throw new \Exception('Table must have at least one column');
        }

        $columns = [];
        foreach ($table['columns'] as $columnArr) {
            try {
                if (!isset($columnArr['name'])) {
                    throw new \Exception('Column name is required');
                }
                if (!isset($columnArr['type'])) {
                    throw new \Exception('Column type is required for column '.$columnArr['name']);
                }

                $column = Column::make($columnArr, $name);
                $columns[$column->getName()] = $column;
            } catch (\Exception $e) {
                throw new \Exception('Error creating column '.$columnArr['name'].': '.$e->getMessage());
            }
        }

        $indexes = [];
        if (isset($table['indexes']) && is_array($table['indexes'])) {
            foreach ($table['indexes'] as $indexArr) {
                try {
                    $index = Index::make($indexArr);
                    $indexes[$index->getName()] = $index;
                } catch (\Exception $e) {
                    $indexName = $indexArr['name'] ?? 'unknown';
                    throw new \Exception('Error creating index '.$indexName.': '.$e->getMessage());
                }
            }
        }

        $foreignKeys = [];
        if (isset($table['foreignKeys']) && is_array($table['foreignKeys'])) {
            foreach ($table['foreignKeys'] as $foreignKeyArr) {
                try {
                    $foreignKey = ForeignKey::make($foreignKeyArr);
                    $foreignKeys[$foreignKey->getName()] = $foreignKey;
                } catch (\Exception $e) {
                    $fkName = $foreignKeyArr['name'] ?? 'unknown';
                    throw new \Exception('Error creating foreign key '.$fkName.': '.$e->getMessage());
                }
            }
        }

        $options = $table['options'] ?? [];

        try {
            return new self($name, $columns, $indexes, [], $foreignKeys, $options);
        } catch (\Exception $e) {
            throw new \Exception('Error creating table object: '.$e->getMessage());
        }
    }

    public function getColumnsIndexes($columns, $sort = false)
    {
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        $matched = [];

        foreach ($this->_indexes as $index) {
            if ($index->spansColumns($columns)) {
                $matched[$index->getName()] = $index;
            }
        }

        if (count($matched) > 1 && $sort) {
            // Sort indexes based on priority: PRI > UNI > IND
            uasort($matched, function ($index1, $index2) {
                $index1_type = Index::getType($index1);
                $index2_type = Index::getType($index2);

                if ($index1_type == $index2_type) {
                    return 0;
                }

                if ($index1_type == Index::PRIMARY) {
                    return -1;
                }

                if ($index2_type == Index::PRIMARY) {
                    return 1;
                }

                if ($index1_type == Index::UNIQUE) {
                    return -1;
                }

                // If we reach here, it means: $index1=INDEX && $index2=UNIQUE
                return 1;
            });
        }

        return $matched;
    }

    public function diff(DoctrineTable $compareTable)
    {
        return (new Comparator(SchemaManager::getDatabasePlatform()))->compareTables($this, $compareTable);
    }

    public function diffOriginal()
    {
        return (new Comparator(SchemaManager::getDatabasePlatform()))->compareTables(SchemaManager::getDoctrineTable($this->_name), $this);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'name'           => $this->_name,
            'oldName'        => $this->_name,
            'columns'        => $this->exportColumnsToArray(),
            'indexes'        => $this->exportIndexesToArray(),
            'primaryKeyName' => $this->_primaryKeyName,
            'foreignKeys'    => $this->exportForeignKeysToArray(),
            'options'        => $this->_options,
        ];
    }

    /**
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * @return array
     */
    public function exportColumnsToArray()
    {
        $exportedColumns = [];

        foreach ($this->getColumns() as $name => $column) {
            $exportedColumns[] = Column::toArray($column);
        }

        return $exportedColumns;
    }

    /**
     * @return array
     */
    public function exportIndexesToArray()
    {
        $exportedIndexes = [];

        foreach ($this->getIndexes() as $name => $index) {
            $indexArr = Index::toArray($index);
            $indexArr['table'] = $this->_name;
            $exportedIndexes[] = $indexArr;
        }

        return $exportedIndexes;
    }

    /**
     * @return array
     */
    public function exportForeignKeysToArray()
    {
        $exportedForeignKeys = [];

        foreach ($this->getForeignKeys() as $name => $fk) {
            $exportedForeignKeys[$name] = ForeignKey::toArray($fk);
        }

        return $exportedForeignKeys;
    }

    public function __get($property)
    {
        $getter = 'get'.ucfirst($property);

        if (!method_exists($this, $getter)) {
            throw new \Exception("Property {$property} doesn't exist or is unavailable");
        }

        return $this->$getter();
    }
}
