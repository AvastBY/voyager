<?php

namespace TCG\Voyager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Thumb extends Model
{
    protected $table = 'thumbs';
    
    protected $fillable = [
        'mark',
        'width',
        'height',
        'cover',
        'fix_canvas',
        'upsize',
        'quality',
        'blur',
        'canvas_color'
    ];

    protected $casts = [
        'cover' => 'boolean',
        'fix_canvas' => 'boolean',
        'upsize' => 'boolean',
        'width' => 'integer',
        'height' => 'integer',
        'quality' => 'integer',
        'blur' => 'integer'
    ];

    public static function boot()
    {
        parent::boot();

        self::updated(function ($model) {
            // Очищаем тумбсы при обновлении настроек
            $thumbs_path = storage_path().'/app/public/_thumbs';
            $directories = glob($thumbs_path . '/*/*/*/*/'.$model->mark , GLOB_ONLYDIR);
            if($directories){
                foreach ($directories as $key => $path) {
                    $files = scandir($path);
                    if($files){
                        foreach ($files as $key1 => $filename) {
                            if(!$filename || $filename == '.' || $filename == '..') continue;
                            unlink($path.'/'.$filename);
                        }
                    }
                    rmdir($path);
                }
            }
        });
    }
}
