# Voyager Thumbs Module

Модуль для создания и управления thumbnails изображений в админке Voyager с поддержкой Intervention Image 3.0.

## Установка

1. Модуль уже интегрирован в Voyager
2. Запустите команду установки:
```bash
php artisan voyager:install-thumbs
```

3. Очистите кеш:
```bash
php artisan cache:clear
```

4. (Опционально) Опубликуйте конфигурацию:
```bash
php artisan vendor:publish --tag=thumbs_config
```

**Важно:** После установки убедитесь, что у вас создан символический линк для storage:
```bash
php artisan storage:link
```

## Команды

### Установка модуля
```bash
php artisan voyager:install-thumbs
```

### Очистка thumbnails
```bash
# Очистить только thumbnails (оставить placeholders)
php artisan voyager:clear-thumbs

# Очистить все включая placeholders
php artisan voyager:clear-thumbs --all
```

## Использование

### В моделях

Добавьте trait `Thumbs` к вашей модели:

```php
use TCG\Voyager\Traits\Thumbs;

class Post extends Model
{
    use Thumbs;
    
    // ... остальной код модели
}
```

### Генерация thumbnails

#### Простое изображение
```php
$post = Post::find(1);

// Получить thumbnail
$thumbUrl = $post->thumb('image', 'small');

// Получить thumbnail без WebP
$thumbUrl = $post->thumbNotWebp('image', 'small');

// Получить placeholder если изображения нет
$placeholder = $post->placeholder('small');
```

#### Галерея изображений
```php
$post = Post::find(1);

// Получить массив объектов галереи с thumbnails
$gallery = $post->thumbGallery('images');

foreach ($gallery as $image) {
    $thumbUrl = $post->galleryThumb($image, 'gallery');
}
```

#### HTML для picture элемента
```php
// Генерирует HTML для picture элемента с поддержкой WebP
$pictureHtml = $post->pictureSource('image', 'medium');

// Для галереи
$pictureHtml = $post->pictureGallerySource($image, 'gallery');
```

### Настройки thumbnails

В админке Voyager перейдите в раздел "Thumbs" для управления настройками:

- **Mark** - уникальный идентификатор настройки
- **Width/Height** - размеры thumbnail
- **Cover** - обрезать изображение по размерам
- **Fix Canvas** - зафиксировать размер холста
- **Upsize** - увеличивать изображение если оно меньше
- **Quality** - качество JPEG (1-100)
- **Blur** - размытие (0-100)
- **Canvas Color** - цвет фона для fix_canvas

### Предустановленные настройки

После установки доступны следующие настройки:

- `small` - 150x150, cover
- `medium` - 300x300, fix_canvas с белым фоном
- `large` - 600x400, cover, upsize
- `gallery` - 200x200, cover
- `blurred` - 400x300, fix_canvas, blur 15

### Очистка thumbnails

```php
$post = Post::find(1);

// Очистить все thumbnails для поста
$post->clearThumbs();

// Очистить все placeholders
$post->clearPlaceholders();
```

## Структура файлов

Thumbnails сохраняются в:
```
storage/app/public/_thumbs/{table}/{folder}/{id}/{field}/{mark}/{hash}.{ext}
```

Галереи:
```
storage/app/public/_thumbs/{table}/{folder}/{id}/gallery/{field}/{mark}/{hash}.{ext}
```

Placeholders:
```
storage/app/public/_thumbs/placeholders/{mark}.jpg
```

## Безопасность

- Все URL thumbnails содержат хеш для безопасности
- Хеш генерируется на основе параметров изображения
- Поддерживается настройка соли через `THUMBS_SALT` в .env

## Поддержка форматов

- JPEG/JPG
- PNG
- WebP (автоматически конвертируется в PNG для совместимости)

## Производительность

- Thumbnails генерируются по требованию
- Автоматически очищаются при обновлении модели
- Кешируются в файловой системе
