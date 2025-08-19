<?php

namespace TCG\Voyager\Database\Types\Mysql;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use TCG\Voyager\Database\Types\Type;

class VarBinaryType extends Type
{
    public const NAME = 'varbinary';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] = empty($column['length']) ? 255 : $column['length'];

        return "varbinary({$column['length']})";
    }
}
