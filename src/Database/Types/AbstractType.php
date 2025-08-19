<?php

namespace TCG\Voyager\Database\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type as DoctrineType;

abstract class AbstractType extends DoctrineType
{
    public const NAME = 'UNDEFINED_TYPE_NAME';

    public function getName(): string
    {
        return static::NAME;
    }

    abstract public function getSQLDeclaration(array $column, AbstractPlatform $platform): string;

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
} 