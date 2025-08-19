<?php

namespace TCG\Voyager\Database\Types\Postgresql;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use TCG\Voyager\Database\Types\Type;

class BitType extends Type
{
    public const NAME = 'bit';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $length = empty($column['length']) ? 1 : $column['length'];

        return "bit({$length})";
    }
}
