<?php

namespace TCG\Voyager\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use TCG\Voyager\Models\Thumb;
use TCG\Voyager\Models\Post;
use TCG\Voyager\Tests\TestCase;

class ThumbsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_thumb_configuration()
    {
        $thumb = Thumb::create([
            'mark' => 'test',
            'width' => 300,
            'height' => 200,
            'cover' => true,
            'quality' => 85
        ]);

        $this->assertDatabaseHas('thumbs', [
            'mark' => 'test',
            'width' => 300,
            'height' => 200
        ]);
    }

    public function test_thumb_model_has_correct_attributes()
    {
        $thumb = new Thumb();
        
        $this->assertTrue(in_array('mark', $thumb->getFillable()));
        $this->assertTrue(in_array('width', $thumb->getFillable()));
        $this->assertTrue(in_array('height', $thumb->getFillable()));
        $this->assertTrue(in_array('cover', $thumb->getFillable()));
        $this->assertTrue(in_array('fix_canvas', $thumb->getFillable()));
        $this->assertTrue(in_array('upsize', $thumb->getFillable()));
        $this->assertTrue(in_array('quality', $thumb->getFillable()));
        $this->assertTrue(in_array('blur', $thumb->getFillable()));
        $this->assertTrue(in_array('canvas_color', $thumb->getFillable()));
    }

    public function test_thumb_model_casts_are_correct()
    {
        $thumb = new Thumb();
        
        $this->assertTrue($thumb->getCasts()['cover'] === 'boolean');
        $this->assertTrue($thumb->getCasts()['fix_canvas'] === 'boolean');
        $this->assertTrue($thumb->getCasts()['upsize'] === 'boolean');
        $this->assertTrue($thumb->getCasts()['width'] === 'integer');
        $this->assertTrue($thumb->getCasts()['height'] === 'integer');
        $this->assertTrue($thumb->getCasts()['quality'] === 'integer');
        $this->assertTrue($thumb->getCasts()['blur'] === 'integer');
    }

    public function test_post_model_uses_thumbs_trait()
    {
        $post = new Post();
        
        $this->assertTrue(method_exists($post, 'thumb'));
        $this->assertTrue(method_exists($post, 'galleryThumb'));
        $this->assertTrue(method_exists($post, 'placeholder'));
        $this->assertTrue(method_exists($post, 'clearThumbs'));
    }

    public function test_page_model_uses_thumbs_trait()
    {
        $page = new \TCG\Voyager\Models\Page();
        
        $this->assertTrue(method_exists($page, 'thumb'));
        $this->assertTrue(method_exists($page, 'galleryThumb'));
        $this->assertTrue(method_exists($page, 'placeholder'));
        $this->assertTrue(method_exists($page, 'clearThumbs'));
    }
}
