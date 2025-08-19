<?php

namespace TCG\Voyager\Database\Types\Mysql;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Database\Types\Type;

class SetType extends Type
{
    public const NAME = 'set';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (empty($column['allowed'] ?? null)) {
            throw new \Exception('SET type requires allowed values to be specified in the column definition');
        }

        $pdo = DB::connection()->getPdo();

        // If allowed is already an array, use it; otherwise try to get from comment
        $allowed = is_array($column['allowed']) 
            ? $column['allowed']
            : (isset($column['comment']) ? explode(',', trim($column['comment'])) : []);

        // trim and quote the values
        $allowed = array_map(function ($value) use ($pdo) {
            return $pdo->quote(trim($value));
        }, $allowed);

        return 'set('.implode(', ', $allowed).')';
    }
}
