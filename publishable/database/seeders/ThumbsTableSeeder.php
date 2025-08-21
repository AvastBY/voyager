<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use TCG\Voyager\Models\Thumb;

class ThumbsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        $thumbs = [
            [
                'mark' => 'small',
                'width' => 150,
                'height' => 150,
                'cover' => true,
                'fix_canvas' => false,
                'upsize' => false,
                'quality' => 90,
                'blur' => 0,
                'canvas_color' => null,
            ],
            [
                'mark' => 'medium',
                'width' => 300,
                'height' => 300,
                'cover' => false,
                'fix_canvas' => true,
                'upsize' => false,
                'quality' => 85,
                'blur' => 0,
                'canvas_color' => '#ffffff',
            ],
            [
                'mark' => 'large',
                'width' => 600,
                'height' => 400,
                'cover' => true,
                'fix_canvas' => false,
                'upsize' => true,
                'quality' => 80,
                'blur' => 0,
                'canvas_color' => null,
            ],
            [
                'mark' => 'gallery',
                'width' => 200,
                'height' => 200,
                'cover' => true,
                'fix_canvas' => false,
                'upsize' => false,
                'quality' => 90,
                'blur' => 0,
                'canvas_color' => null,
            ],
            [
                'mark' => 'blurred',
                'width' => 400,
                'height' => 300,
                'cover' => false,
                'fix_canvas' => true,
                'upsize' => false,
                'quality' => 75,
                'blur' => 15,
                'canvas_color' => '#f0f0f0',
            ],
        ];

        foreach ($thumbs as $thumb) {
            Thumb::updateOrCreate(
                ['mark' => $thumb['mark']],
                $thumb
            );
        }
    }
}
