<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Thumbs Configuration
    |--------------------------------------------------------------------------
    |
    | Здесь вы можете настроить параметры модуля thumbs
    |
    */

    // Соль для генерации хешей (можно переопределить через THUMBS_SALT в .env)
    'salt' => env('THUMBS_SALT', 'A45Scj1381h13ba'),

    // Диск для хранения thumbnails
    'disk' => env('THUMBS_DISK', 'public'),

    // Путь для хранения thumbnails относительно диска
    'path' => env('THUMBS_PATH', '_thumbs'),

    // Путь для хранения placeholders
    'placeholders_path' => env('THUMBS_PLACEHOLDERS_PATH', '_thumbs/placeholders'),

    // Качество по умолчанию для JPEG
    'default_quality' => env('THUMBS_DEFAULT_QUALITY', 90),

    // Максимальное значение размытия
    'max_blur' => env('THUMBS_MAX_BLUR', 100),

    // Поддерживаемые форматы изображений
    'supported_formats' => [
        'jpg', 'jpeg', 'png', 'webp'
    ],

    // Автоматическая очистка thumbnails при обновлении модели
    'auto_clear_on_update' => env('THUMBS_AUTO_CLEAR_ON_UPDATE', true),

    // Автоматическая очистка thumbnails при удалении модели
    'auto_clear_on_delete' => env('THUMBS_AUTO_CLEAR_ON_DELETE', true),

    // Кеширование thumbnails (в секундах)
    'cache_ttl' => env('THUMBS_CACHE_TTL', 3600),

    // Размер папки для группировки записей (для оптимизации файловой системы)
    'folder_size' => env('THUMBS_FOLDER_SIZE', 1000),

    // Настройки по умолчанию для новых thumbnails
    'defaults' => [
        'small' => [
            'width' => 150,
            'height' => 150,
            'cover' => true,
            'quality' => 90,
        ],
        'medium' => [
            'width' => 300,
            'height' => 300,
            'fix_canvas' => true,
            'quality' => 85,
        ],
        'large' => [
            'width' => 600,
            'height' => 400,
            'cover' => true,
            'upsize' => true,
            'quality' => 80,
        ],
    ],
];
