<?php

namespace TCG\Voyager\Database;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Schema\Comparator;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Database\Schema\Table;
use TCG\Voyager\Database\Types\Type;

class DatabaseUpdater
{
    protected $tableArr;
    protected $table;
    protected $originalTable;

    public function __construct(array $tableArr)
    {
        Type::registerCustomPlatformTypes();

        $this->table = Table::make($tableArr);
        $this->tableArr = $tableArr;
        $this->originalTable = SchemaManager::listTableDetails($tableArr['oldName']);
    }

    /**
     * Update the table.
     *
     * @return void
     */
    public static function update($table)
    {
        if (!is_array($table)) {
            $table = json_decode($table, true);
        }

        if (!SchemaManager::tableExists($table['oldName'])) {
            throw SchemaException::tableDoesNotExist($table['oldName']);
        }

        $updater = new self($table);

        $updater->updateTable();
    }

    /**
     * Updates the table.
     *
     * @return void
     */
    public function updateTable()
    {
        // Get table new name
        if (($newName = $this->table->getName()) != $this->originalTable->getName()) {
            // Make sure the new name doesn't already exist
            if (SchemaManager::tableExists($newName)) {
                throw SchemaException::tableAlreadyExists($newName);
            }
        } else {
            $newName = false;
        }

        // Rename columns
        if ($renamedColumnsDiff = $this->getRenamedColumnsDiff()) {
            SchemaManager::alterTable($renamedColumnsDiff);

            // Refresh original table after renaming the columns
            $this->originalTable = SchemaManager::listTableDetails($this->tableArr['oldName']);
        }

        try {
            // Create comparator for proper table comparison
            $comparator = new Comparator(SchemaManager::getDatabasePlatform());
            $tableDiff = $comparator->compareTables($this->originalTable, $this->table);

            // Add new table name to tableDiff
            if ($newName) {
                if (!$tableDiff) {
                    $tableDiff = new TableDiff(
                        $this->originalTable,  // fromTable
                        [],  // addedColumns
                        [],  // changedColumns
                        [],  // removedColumns
                        [],  // addedIndexes
                        [],  // changedIndexes
                        [],  // removedIndexes
                        [],  // addedForeignKeys
                        [],  // changedForeignKeys
                        []   // removedForeignKeys
                    );
                }

                $tableDiff->newName = $newName;
            }

            // Update the table
            if ($tableDiff) {
                // Log table diff for debugging
                \Log::debug('Table diff:', [
                    'name' => $this->originalTable->getName(),
                    'newName' => $tableDiff->newName ?? null,
                    'addedColumns' => array_map(function($col) { 
                        return $col->getName(); 
                    }, $tableDiff->addedColumns ?? []),
                    'changedColumns' => array_keys($tableDiff->changedColumns ?? []),
                    'removedColumns' => array_map(function($col) { 
                        return $col->getName(); 
                    }, $tableDiff->removedColumns ?? [])
                ]);

                SchemaManager::alterTable($tableDiff);
            }
        } catch (\Exception $e) {
            \Log::error('Error in updateTable:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'originalTable' => $this->originalTable->getName(),
                'newTable' => $this->table->getName(),
                'tableDiff' => [
                    'class' => get_class($tableDiff ?? null),
                    'properties' => $tableDiff ? get_object_vars($tableDiff) : []
                ]
            ]);
            throw new \Exception('Error updating table: ' . $e->getMessage());
        }
    }

    /**
     * Get the table diff to rename columns.
     *
     * @return \Doctrine\DBAL\Schema\TableDiff|false
     */
    protected function getRenamedColumnsDiff()
    {
        $renamedColumns = $this->getRenamedColumns();

        if (empty($renamedColumns)) {
            return false;
        }

        try {
            $renamedColumnsDiff = new TableDiff(
                $this->originalTable,  // fromTable
                [],  // addedColumns
                [],  // changedColumns
                [],  // removedColumns
                [],  // addedIndexes
                [],  // changedIndexes
                [],  // removedIndexes
                [],  // addedForeignKeys
                [],  // changedForeignKeys
                []   // removedForeignKeys
            );

            foreach ($renamedColumns as $oldName => $newName) {
                $renamedColumnsDiff->renamedColumns = [
                    $oldName => $this->table->getColumn($newName)
                ];
            }

            return $renamedColumnsDiff;
        } catch (\Exception $e) {
            throw new \Exception('Error creating renamed columns diff: ' . $e->getMessage());
        }
    }

    /**
     * Get the table diff to rename columns and indexes.
     *
     * @return \Doctrine\DBAL\Schema\TableDiff|false
     */
    protected function getRenamedDiff()
    {
        $renamedColumns = $this->getRenamedColumns();
        $renamedIndexes = $this->getRenamedIndexes();

        if (empty($renamedColumns) && empty($renamedIndexes)) {
            return false;
        }

        try {
            $renamedDiff = new TableDiff(
                $this->originalTable,  // fromTable
                [],  // addedColumns
                [],  // changedColumns
                [],  // removedColumns
                [],  // addedIndexes
                [],  // changedIndexes
                [],  // removedIndexes
                [],  // addedForeignKeys
                [],  // changedForeignKeys
                []   // removedForeignKeys
            );

            if (!empty($renamedColumns)) {
                $renamedDiff->renamedColumns = array_map(
                    fn($newName) => $this->table->getColumn($newName),
                    $renamedColumns
                );
            }

            if (!empty($renamedIndexes)) {
                $renamedDiff->renamedIndexes = array_map(
                    fn($newName) => $this->table->getIndex($newName),
                    $renamedIndexes
                );
            }

            return $renamedDiff;
        } catch (\Exception $e) {
            throw new \Exception('Error creating renamed diff: ' . $e->getMessage());
        }
    }

    /**
     * Get columns that were renamed.
     *
     * @return array
     */
    protected function getRenamedColumns()
    {
        $renamedColumns = [];

        foreach ($this->tableArr['columns'] as $column) {
            $oldName = $column['oldName'];

            // make sure this is an existing column and not a new one
            if ($this->originalTable->hasColumn($oldName)) {
                $name = $column['name'];

                if ($name != $oldName) {
                    $renamedColumns[$oldName] = $name;
                }
            }
        }

        return $renamedColumns;
    }

    /**
     * Get indexes that were renamed.
     *
     * @return array
     */
    protected function getRenamedIndexes()
    {
        $renamedIndexes = [];

        foreach ($this->tableArr['indexes'] as $index) {
            $oldName = $index['oldName'];

            // make sure this is an existing index and not a new one
            if ($this->originalTable->hasIndex($oldName)) {
                $name = $index['name'];

                if ($name != $oldName) {
                    $renamedIndexes[$oldName] = $name;
                }
            }
        }

        return $renamedIndexes;
    }
}
