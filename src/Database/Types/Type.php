<?php

namespace TCG\Voyager\Database\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform as DoctrineAbstractPlatform;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Doctrine\DBAL\Types\Types;
use TCG\Voyager\Database\Platforms\Platform;
use TCG\Voyager\Database\Schema\SchemaManager;
use Illuminate\Support\Collection;

abstract class Type extends AbstractType
{
    protected static bool $customTypesRegistered = false;
    /** @var Collection|null */
    protected static $platformTypeMapping = null;
    /** @var Collection|null */
    protected static $allTypes = null;
    /** @var Collection|null */
    protected static $platformTypes = null;
    protected static array $customTypeOptions = [];
    protected static array $typeCategories = [];

    public const NOT_SUPPORTED = 'notSupported';
    public const NOT_SUPPORT_INDEX = 'notSupportIndex';

    // Define our own type constants since Doctrine DBAL 4.0 changed them
    private const TYPE_ARRAY = 'array';
    private const TYPE_SIMPLE_ARRAY = 'simple_array';
    private const TYPE_JSON = 'json';
    private const TYPE_STRING = 'string';
    private const TYPE_TEXT = 'text';
    private const TYPE_GUID = 'guid';
    private const TYPE_BOOLEAN = 'boolean';
    private const TYPE_SMALLINT = 'smallint';
    private const TYPE_INTEGER = 'integer';
    private const TYPE_BIGINT = 'bigint';
    private const TYPE_DECIMAL = 'decimal';
    private const TYPE_FLOAT = 'float';
    private const TYPE_BINARY = 'binary';
    private const TYPE_BLOB = 'blob';
    private const TYPE_DATE_MUTABLE = 'date';
    private const TYPE_DATETIME_MUTABLE = 'datetime';
    private const TYPE_DATETIMETZ_MUTABLE = 'datetimetz';
    private const TYPE_TIME_MUTABLE = 'time';

    public function getName(): string
    {
        return static::NAME;
    }

    public function getSQLDeclaration(array $column, DoctrineAbstractPlatform $platform): string
    {
        // Реализация должна быть в дочерних классах
        throw new \RuntimeException(sprintf('Method getSQLDeclaration() must be implemented for type %s', static::NAME));
    }

    public static function toArray(DoctrineType $type): array
    {
        $customTypeOptions = $type->customOptions ?? [];
        $name = static::getTypeName($type);

        return array_merge([
            'name' => $name,
        ], $customTypeOptions);
    }

    protected static function getTypeName(DoctrineType $type): string
    {
        if ($type instanceof self) {
            return $type::NAME;
        }

        // Map Doctrine types to our types
        $typeMap = [
            Types::STRING => 'varchar',
            Types::TEXT => 'text',
            Types::BOOLEAN => 'boolean',
            Types::SMALLINT => 'smallint',
            Types::INTEGER => 'integer',
            Types::BIGINT => 'bigint',
            Types::DECIMAL => 'decimal',
            Types::FLOAT => 'float',
            Types::BINARY => 'binary',
            Types::BLOB => 'blob',
            Types::DATE_MUTABLE => 'date',
            Types::DATETIME_MUTABLE => 'datetime',
            Types::DATETIMETZ_MUTABLE => 'datetimetz',
            Types::TIME_MUTABLE => 'time',
            Types::JSON => 'json',
        ];

        $className = get_class($type);
        $typeName = substr($className, strrpos($className, '\\') + 1);
        $typeName = str_replace('Type', '', $typeName);
        $typeName = strtolower($typeName);

        return $typeMap[$typeName] ?? $typeName;
    }

    public static function getPlatformTypes(): Collection
    {
        if (static::$platformTypes !== null) {
            return static::$platformTypes;
        }

        if (!static::$customTypesRegistered) {
            static::registerCustomPlatformTypes();
        }

        $platform = SchemaManager::getDatabasePlatform();
        
        // В Doctrine DBAL 4.0 нет метода getName(), определяем тип платформы по классу
        $platformName = 'mysql';
        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            $platformName = 'postgresql';
        } elseif ($platform instanceof \Doctrine\DBAL\Platforms\SQLitePlatform) {
            $platformName = 'sqlite';
        } elseif ($platform instanceof \Doctrine\DBAL\Platforms\SQLServerPlatform) {
            $platformName = 'sqlserver';
        }

        static::$platformTypes = Platform::getPlatformTypes(
            $platformName,
            static::getPlatformTypeMapping($platform)
        );

        static::$platformTypes = static::$platformTypes->map(function ($type) {
            return static::toArray(static::getType($type));
        })->groupBy('category');

        return static::$platformTypes;
    }

    public static function getPlatformTypeMapping(DoctrineAbstractPlatform $platform): Collection
    {
        if (static::$platformTypeMapping !== null) {
            return static::$platformTypeMapping;
        }

        static::$platformTypeMapping = collect([
            "bigint" => "bigint",
            "binary" => "binary",
            "blob" => "blob",
            "char" => "char",
            "date" => "date",
            "datetime" => "datetime",
            "decimal" => "decimal",
            "double" => "double",
            "float" => "float",
            "int" => "integer",
            "integer" => "integer",
            "longblob" => "longblob",
            "longtext" => "longtext",
            "mediumblob" => "mediumblob",
            "mediumint" => "mediumint",
            "mediumtext" => "mediumtext",
            "numeric" => "numeric",
            "real" => "float",
            "set" => "set",
            "smallint" => "smallint",
            "varchar" => "varchar",
            "text" => "text",
            "time" => "time",
            "timestamp" => "timestamp",
            "tinyblob" => "tinyblob",
            "tinyint" => "tinyint",
            "tinytext" => "tinytext",
            "varbinary" => "varbinary",
            "year" => "year",
            "json" => "json",
            "bit" => "bit",
            "enum" => "enum",
            "geometrycollection" => "geometrycollection",
            "geometry" => "geometry",
            "linestring" => "linestring",
            "multilinestring" => "multilinestring",
            "multipoint" => "multipoint",
            "multipolygon" => "multipolygon",
            "point" => "point",
            "polygon" => "polygon"
        ]);

        return static::$platformTypeMapping;
    }

    public static function registerCustomPlatformTypes(bool $force = false): void
    {
        if (static::$customTypesRegistered && !$force) {
            return;
        }

        $platformName = 'Mysql';

        $customTypes = array_merge(
            static::getPlatformCustomTypes('Common'),
            static::getPlatformCustomTypes($platformName)
        );

        foreach ($customTypes as $type) {
            $name = $type::NAME;

            if (static::hasType($name)) {
                static::overrideType($name, $type);
            } else {
                static::addType($name, $type);
            }

            $dbType = defined("{$type}::DBTYPE") ? $type::DBTYPE : $name;

            $platform = SchemaManager::getDatabasePlatform();
            $platform->registerDoctrineTypeMapping($dbType, $name);
        }

        static::addCustomTypeOptions($platformName);

        static::$customTypesRegistered = true;
    }

    protected static function addCustomTypeOptions(string $platformName): void
    {
        static::registerCommonCustomTypeOptions();

        Platform::registerPlatformCustomTypeOptions($platformName);

        foreach (static::$customTypeOptions as $option) {
            foreach ($option['types'] as $type) {
                if (static::hasType($type)) {
                    static::getType($type)->customOptions[$option['name']] = $option['value'];
                }
            }
        }
    }

    protected static function getPlatformCustomTypes(string $platformName): array
    {
        $typesPath = __DIR__.DIRECTORY_SEPARATOR.$platformName.DIRECTORY_SEPARATOR;
        $namespace = __NAMESPACE__.'\\'.$platformName.'\\';
        $types = [];

        foreach (glob($typesPath.'*.php') as $classFile) {
            $types[] = $namespace.str_replace(
                '.php',
                '',
                str_replace($typesPath, '', $classFile)
            );
        }

        return $types;
    }

    public static function registerCustomOption(string $name, $value, $types): void
    {
        if (is_string($types)) {
            $types = trim($types);

            if ($types == '*') {
                $types = static::getAllTypes()->toArray();
            } elseif (strpos($types, '*') !== false) {
                $searchType = str_replace('*', '', $types);
                $types = static::getAllTypes()->filter(function ($type) use ($searchType) {
                    return strpos($type, $searchType) !== false;
                })->toArray();
            } else {
                $types = [$types];
            }
        }

        static::$customTypeOptions[] = [
            'name'  => $name,
            'value' => $value,
            'types' => $types,
        ];
    }

    protected static function registerCommonCustomTypeOptions(): void
    {
        static::registerTypeCategories();
        static::registerTypeDefaultOptions();
    }

    protected static function registerTypeDefaultOptions(): void
    {
        $types = static::getTypeCategories();

        // Numbers
        static::registerCustomOption('default', [
            'type' => 'number',
            'step' => 'any',
        ], $types['numbers']);

        // Date and Time
        static::registerCustomOption('default', [
            'type' => 'date',
        ], 'date');
        static::registerCustomOption('default', [
            'type' => 'time',
            'step' => '1',
        ], 'time');
        static::registerCustomOption('default', [
            'type' => 'number',
            'min'  => '0',
        ], 'year');
    }

    protected static function registerTypeCategories(): void
    {
        $types = static::getTypeCategories();

        static::registerCustomOption('category', 'Numbers', $types['numbers']);
        static::registerCustomOption('category', 'Strings', $types['strings']);
        static::registerCustomOption('category', 'Date and Time', $types['datetime']);
        static::registerCustomOption('category', 'Lists', $types['lists']);
        static::registerCustomOption('category', 'Binary', $types['binary']);
        static::registerCustomOption('category', 'Geometry', $types['geometry']);
    }

    /**
     * @return Collection
     */
    public static function getAllTypes(): Collection
    {
        if (static::$allTypes === null) {
            static::$allTypes = collect(static::getTypeCategories())->flatten();
        }

        return static::$allTypes;
    }

    public static function getTypeCategories(): array
    {
        if (static::$typeCategories) {
            return static::$typeCategories;
        }

        $numbers = [
            'tinyint',
            'smallint',
            'mediumint',
            'integer',
            'int',
            'bigint',
            'decimal',
            'numeric',
            'float',
            'double',
            'real',
            'boolean',
            'serial',
        ];

        $strings = [
            'char',
            'varchar',
            'tinytext',
            'text',
            'mediumtext',
            'longtext',
            'uuid',
        ];

        $datetime = [
            'date',
            'datetime',
            'timestamp',
            'time',
            'year',
        ];

        $lists = [
            'enum',
            'set',
            'json',
        ];

        $binary = [
            'bit',
            'binary',
            'varbinary',
            'tinyblob',
            'blob',
            'mediumblob',
            'longblob',
        ];

        $geometry = [
            'geometry',
            'point',
            'linestring',
            'polygon',
            'multipoint',
            'multilinestring',
            'multipolygon',
            'geometrycollection',
        ];

        static::$typeCategories = [
            'numbers'  => $numbers,
            'strings'  => $strings,
            'datetime' => $datetime,
            'lists'    => $lists,
            'binary'   => $binary,
            'geometry' => $geometry,
        ];

        return static::$typeCategories;
    }
}