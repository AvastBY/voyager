<?php

namespace TCG\Voyager\Database\Types\Mysql;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use TCG\Voyager\Database\Types\Type;

class TimeStampType extends Type
{
    public const NAME = 'timestamp';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        if (isset($column['default'])) {
            return 'timestamp';
        }

        return 'timestamp null';
    }
}
