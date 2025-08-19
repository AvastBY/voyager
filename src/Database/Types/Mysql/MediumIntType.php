<?php

namespace TCG\Voyager\Database\Types\Mysql;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use TCG\Voyager\Database\Types\Type;

class MediumIntType extends Type
{
    public const NAME = 'mediumint';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $commonIntegerTypeDeclaration = call_protected_method($platform, '_getCommonIntegerTypeDeclarationSQL', $column);

        return 'mediumint'.$commonIntegerTypeDeclaration;
    }
}
