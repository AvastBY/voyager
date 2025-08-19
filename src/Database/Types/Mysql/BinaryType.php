<?php

namespace TCG\Voyager\Database\Types\Mysql;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use TCG\Voyager\Database\Types\Type;

class BinaryType extends Type
{
    public const NAME = 'binary';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = empty($column['length']) ? 255 : $column['length'];

        return "binary({$column['length']})";
    }
}
