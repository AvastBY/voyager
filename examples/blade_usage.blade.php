{{-- Пример использования модуля thumbs в Blade шаблонах --}}

{{-- Простое изображение с thumbnail --}}
<div class="post-image">
    @if($post->image)
        <img src="{{ $post->thumb('image', 'medium') }}" 
             alt="{{ $post->title }}" 
             class="img-fluid">
    @else
        <img src="{{ $post->placeholder('medium') }}" 
             alt="Placeholder" 
             class="img-fluid">
    @endif
</div>

{{-- Галерея изображений с thumbnails --}}
<div class="post-gallery">
    @if($post->images)
        @foreach($post->thumbGallery('images') as $image)
            <div class="gallery-item">
                <img src="{{ $post->galleryThumb($image, 'gallery') }}" 
                     alt="Gallery image" 
                     class="img-fluid">
            </div>
        @endforeach
    @endif
</div>

{{-- Picture элемент с поддержкой WebP --}}
<div class="responsive-image">
    {!! $post->pictureSource('image', 'large') !!}
</div>

{{-- Адаптивные изображения для разных размеров экрана --}}
<div class="responsive-images">
    <img src="{{ $post->thumb('image', 'small') }}" 
         alt="{{ $post->title }}" 
         class="img-fluid d-block d-md-none">
    
    <img src="{{ $post->thumb('image', 'medium') }}" 
         alt="{{ $post->title }}" 
         class="img-fluid d-none d-md-block d-lg-none">
    
    <img src="{{ $post->thumb('image', 'large') }}" 
         alt="{{ $post->title }}" 
         class="img-fluid d-none d-lg-block">
</div>

{{-- Lazy loading с placeholder --}}
<div class="lazy-image">
    <img src="{{ $post->placeholder('medium') }}" 
         data-src="{{ $post->thumb('image', 'medium') }}" 
         alt="{{ $post->title }}" 
         class="img-fluid lazy">
</div>

{{-- Карточка поста с оптимизированным изображением --}}
<div class="post-card">
    <div class="card">
        <div class="card-img-top">
            @if($post->image)
                <img src="{{ $post->thumb('image', 'medium') }}" 
                     alt="{{ $post->title }}" 
                     class="img-fluid">
            @else
                <img src="{{ $post->placeholder('medium') }}" 
                     alt="No image" 
                     class="img-fluid">
            @endif
        </div>
        <div class="card-body">
            <h5 class="card-title">{{ $post->title }}</h5>
            <p class="card-text">{{ Str::limit($post->excerpt, 100) }}</p>
        </div>
    </div>
</div>

{{-- Слайдер с thumbnails --}}
<div class="image-slider">
    @if($post->images)
        <div class="slider-main">
            @foreach($post->thumbGallery('images') as $image)
                <div class="slide">
                    <img src="{{ $post->galleryThumb($image, 'large') }}" 
                         alt="Slide image" 
                         class="img-fluid">
                </div>
            @endforeach
        </div>
        
        <div class="slider-thumbs">
            @foreach($post->thumbGallery('images') as $image)
                <div class="thumb">
                    <img src="{{ $post->galleryThumb($image, 'small') }}" 
                         alt="Thumbnail" 
                         class="img-fluid">
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- CSS для lazy loading --}}
<style>
.lazy {
    opacity: 0;
    transition: opacity 0.3s;
}

.lazy.loaded {
    opacity: 1;
}
</style>

{{-- JavaScript для lazy loading --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const lazyImages = document.querySelectorAll('.lazy');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('loaded');
                observer.unobserve(img);
            }
        });
    });
    
    lazyImages.forEach(img => imageObserver.observe(img));
});
</script>
