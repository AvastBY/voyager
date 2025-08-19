<?php

namespace TCG\Voyager\Database\Schema;

use Doctrine\DBAL\Schema\Column as DoctrineColumn;
use Doctrine\DBAL\Types\Type as DoctrineType;
use TCG\Voyager\Database\Types\Type;

abstract class Column
{
    public static function make(array $column, string $tableName = null)
    {
        if (!isset($column['name'])) {
            throw new \Exception('Column name is required');
        }

        $name = Identifier::validate($column['name'], 'Column');

        if (!isset($column['type'])) {
            throw new \Exception('Column type is required for column '.$name);
        }

        // Handle type
        $type = $column['type'];
        if (is_string($type)) {
            $type = ['name' => $type];
        } elseif (!is_array($type) && !($type instanceof DoctrineType)) {
            throw new \Exception('Invalid type for column '.$name.': '.gettype($type));
        }

        try {
            if ($type instanceof DoctrineType) {
                $doctrineType = $type;
            } else {
                if (!isset($type['name'])) {
                    throw new \Exception('Type name is required for column '.$name);
                }
                $typeName = trim($type['name']);
                if (empty($typeName)) {
                    throw new \Exception('Type name cannot be empty for column '.$name);
                }
                $doctrineType = DoctrineType::getType($typeName);
            }
            $doctrineType->tableName = $tableName;
            $type = $doctrineType;
        } catch (\Exception $e) {
            throw new \Exception('Error creating type for column '.$name.': '.$e->getMessage());
        }

        $options = array_diff_key($column, array_flip(['name', 'composite', 'oldName', 'null', 'extra', 'type', 'charset', 'collation']));

        if (isset($column['length'])) {
            $options['length'] = $column['length'];
        } else if (in_array($type->getTypeRegistry()->lookupName($type), ['string', 'varchar'])) {
            $options['length'] = 255; // Default length for varchar
        }

        if (isset($column['precision'])) {
            $options['precision'] = $column['precision'];
        }

        if (isset($column['scale'])) {
            $options['scale'] = $column['scale'];
        }

        if (isset($column['unsigned']) && $column['unsigned']) {
            $options['unsigned'] = true;
        }

        if (isset($column['notnull'])) {
            $options['notnull'] = (bool) $column['notnull'];
        }

        if (isset($column['default'])) {
            $options['default'] = $column['default'];
        }

        if (isset($column['autoincrement']) && $column['autoincrement']) {
            $options['autoincrement'] = true;
        }

        if (isset($column['comment'])) {
            $options['comment'] = $column['comment'];
        }

        try {
            return new DoctrineColumn($name, $type, $options);
        } catch (\Exception $e) {
            throw new \Exception('Error creating column '.$name.': '.$e->getMessage()."\nType: ".print_r($type, true)."\nOptions: ".print_r($options, true));
        }
    }

    /**
     * @return array
     */
    public static function toArray(DoctrineColumn $column)
    {
        $columnArr = $column->toArray();
        $type = $column->getType();
        $columnArr['type'] = [
            'name' => $type->getTypeRegistry()->lookupName($type)
        ];
        $columnArr['oldName'] = $columnArr['name'];
        $columnArr['null'] = $columnArr['notnull'] ? 'NO' : 'YES';
        $columnArr['extra'] = static::getExtra($column);
        $columnArr['composite'] = false;

        return $columnArr;
    }

    /**
     * @return string
     */
    protected static function getExtra(DoctrineColumn $column)
    {
        $extra = '';

        $extra .= $column->getAutoincrement() ? 'auto_increment' : '';
        // todo: Add Extra stuff like mysql 'onUpdate' etc...

        return $extra;
    }
}
