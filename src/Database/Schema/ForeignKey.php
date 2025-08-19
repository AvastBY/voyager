<?php

namespace TCG\Voyager\Database\Schema;

use Doctrine\DBAL\Schema\ForeignKeyConstraint as DoctrineForeignKey;

abstract class ForeignKey
{
    public static function make(array $foreignKey)
    {
        // Validate required fields
        if (!isset($foreignKey['localColumns'])) {
            throw new \Exception('Local columns are required for foreign key');
        }
        if (!isset($foreignKey['foreignTable'])) {
            throw new \Exception('Foreign table is required for foreign key');
        }
        if (!isset($foreignKey['foreignColumns'])) {
            throw new \Exception('Foreign columns are required for foreign key');
        }

        // Set the local table
        $localTable = null;
        if (isset($foreignKey['localTable'])) {
            try {
                $localTable = SchemaManager::getDoctrineTable($foreignKey['localTable']);
            } catch (\Exception $e) {
                throw new \Exception('Error getting local table: '.$e->getMessage());
            }
        }

        $localColumns = $foreignKey['localColumns'];
        if (!is_array($localColumns)) {
            $localColumns = [$localColumns];
        }
        if (empty($localColumns)) {
            throw new \Exception('Foreign key must have at least one local column');
        }

        $foreignTable = $foreignKey['foreignTable'];
        $foreignColumns = $foreignKey['foreignColumns'];
        if (!is_array($foreignColumns)) {
            $foreignColumns = [$foreignColumns];
        }
        if (empty($foreignColumns)) {
            throw new \Exception('Foreign key must have at least one foreign column');
        }

        if (count($localColumns) !== count($foreignColumns)) {
            throw new \Exception('Number of local and foreign columns must match');
        }

        $options = $foreignKey['options'] ?? [];

        // Set the name
        $name = isset($foreignKey['name']) ? trim($foreignKey['name']) : '';
        if (empty($name)) {
            $table = isset($localTable) ? $localTable->getName() : null;
            try {
                $name = Index::createName($localColumns, 'foreign', $table);
            } catch (\Exception $e) {
                throw new \Exception('Error creating foreign key name: '.$e->getMessage());
            }
        } else {
            try {
                $name = Identifier::validate($name, 'Foreign Key');
            } catch (\Exception $e) {
                throw new \Exception('Invalid foreign key name: '.$e->getMessage());
            }
        }

        try {
            $doctrineForeignKey = new DoctrineForeignKey(
                $localColumns,
                $foreignTable,
                $foreignColumns,
                $name,
                $options
            );

            if (isset($localTable)) {
                $doctrineForeignKey->setLocalTable($localTable);
            }

            return $doctrineForeignKey;
        } catch (\Exception $e) {
            throw new \Exception('Error creating foreign key '.$name.': '.$e->getMessage());
        }
    }

    /**
     * @return array
     */
    public static function toArray(DoctrineForeignKey $fk)
    {
        return [
            'name'           => $fk->getName(),
            'localTable'     => $fk->getLocalTableName(),
            'localColumns'   => $fk->getLocalColumns(),
            'foreignTable'   => $fk->getForeignTableName(),
            'foreignColumns' => $fk->getForeignColumns(),
            'options'        => $fk->getOptions(),
        ];
    }
}
