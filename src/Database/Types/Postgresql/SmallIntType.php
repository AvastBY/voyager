<?php

namespace TCG\Voyager\Database\Types\Postgresql;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use TCG\Voyager\Database\Types\Type;

class SmallIntType extends Type
{
    public const NAME = 'smallint';
    public const DBTYPE = 'int2';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $commonIntegerTypeDeclaration = call_protected_method($platform, '_getCommonIntegerTypeDeclarationSQL', $column);

        $type = $column['autoincrement'] ? 'smallserial' : 'smallint';

        return $type.$commonIntegerTypeDeclaration;
    }
}
