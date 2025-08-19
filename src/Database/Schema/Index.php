<?php

namespace TCG\Voyager\Database\Schema;

use Doctrine\DBAL\Schema\Index as DoctrineIndex;

abstract class Index
{
    public const PRIMARY = 'PRIMARY';
    public const UNIQUE = 'UNIQUE';
    public const INDEX = 'INDEX';

    public static function make(array $index)
    {
        if (!isset($index['columns'])) {
            throw new \Exception('Index columns are required');
        }

        $columns = $index['columns'];
        if (!is_array($columns)) {
            $columns = [$columns];
        }

        if (empty($columns)) {
            throw new \Exception('Index must have at least one column');
        }

        if (isset($index['type'])) {
            $type = $index['type'];

            $isPrimary = ($type == static::PRIMARY);
            $isUnique = $isPrimary || ($type == static::UNIQUE);
        } else {
            $isPrimary = $index['isPrimary'] ?? false;
            $isUnique = $index['isUnique'] ?? false;

            // Set the type
            if ($isPrimary) {
                $type = static::PRIMARY;
            } elseif ($isUnique) {
                $type = static::UNIQUE;
            } else {
                $type = static::INDEX;
            }
        }

        // Set the name
        $name = trim($index['name'] ?? '');
        if (empty($name)) {
            $table = $index['table'] ?? null;
            try {
                $name = static::createName($columns, $type, $table);
            } catch (\Exception $e) {
                throw new \Exception('Error creating index name: '.$e->getMessage());
            }
        } else {
            try {
                $name = Identifier::validate($name, 'Index');
            } catch (\Exception $e) {
                throw new \Exception('Invalid index name: '.$e->getMessage());
            }
        }

        $flags = $index['flags'] ?? [];
        $options = $index['options'] ?? [];

        try {
            return new DoctrineIndex($name, $columns, $isUnique, $isPrimary, $flags, $options);
        } catch (\Exception $e) {
            throw new \Exception('Error creating index '.$name.': '.$e->getMessage());
        }
    }

    /**
     * @return array
     */
    public static function toArray(DoctrineIndex $index)
    {
        $name = $index->getName();
        $columns = $index->getColumns();

        return [
            'name'        => $name,
            'oldName'     => $name,
            'columns'     => $columns,
            'type'        => static::getType($index),
            'isPrimary'   => $index->isPrimary(),
            'isUnique'    => $index->isUnique(),
            'isComposite' => count($columns) > 1,
            'flags'       => $index->getFlags(),
            'options'     => $index->getOptions(),
        ];
    }

    public static function getType(DoctrineIndex $index)
    {
        if ($index->isPrimary()) {
            return static::PRIMARY;
        } elseif ($index->isUnique()) {
            return static::UNIQUE;
        } else {
            return static::INDEX;
        }
    }

    /**
     * Create a default index name.
     *
     * @param array  $columns
     * @param string $type
     * @param string $table
     *
     * @return string
     */
    public static function createName(array $columns, $type, $table = null)
    {
        $table = isset($table) ? trim($table).'_' : '';
        $type = trim($type);
        $name = strtolower($table.implode('_', $columns).'_'.$type);

        return str_replace(['-', '.'], '_', $name);
    }

    public static function availableTypes()
    {
        return [
            static::PRIMARY,
            static::UNIQUE,
            static::INDEX,
        ];
    }
}
