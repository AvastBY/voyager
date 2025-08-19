<?php

namespace TCG\Voyager\Http\Controllers\ContentTypes;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\GifEncoder;
use Intervention\Image\Encoders\WebpEncoder;

class Image extends BaseType
{
    protected $manager;

    public function __construct($request, $slug, $row, $options)
    {
        parent::__construct($request, $slug, $row, $options);
        $this->manager = new ImageManager(new Driver());
    }

    protected function getEncoder($extension, $quality = 75)
    {
        switch (strtolower($extension)) {
            case 'jpg':
            case 'jpeg':
                return new JpegEncoder($quality);
            case 'png':
                return new PngEncoder();
            case 'gif':
                return new GifEncoder();
            case 'webp':
                return new WebpEncoder($quality);
            default:
                return new JpegEncoder($quality);
        }
    }

    protected function autoRotateImage($image)
    {
        try {
            $exif = @exif_read_data($this->request->file($this->row->field));
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        return $image->rotate(180);
                    case 6:
                        return $image->rotate(90);
                    case 8:
                        return $image->rotate(270);
                }
            }
        } catch (\Exception $e) {
            // If EXIF reading fails, return original image
        }
        return $image;
    }

    public function handle()
    {
        if ($this->request->hasFile($this->row->field)) {
            $file = $this->request->file($this->row->field);

            $path = $this->slug.DIRECTORY_SEPARATOR.date('FY').DIRECTORY_SEPARATOR;

            $filename = $this->generateFileName($file, $path);

            $image = $this->manager->read($file);
            $image = $this->autoRotateImage($image);

            $fullPath = $path.$filename.'.'.$file->getClientOriginalExtension();

            $resize_width = null;
            $resize_height = null;
            if (isset($this->options->resize) && (
                isset($this->options->resize->width) || isset($this->options->resize->height)
            )) {
                if (isset($this->options->resize->width)) {
                    $resize_width = $this->options->resize->width;
                }
                if (isset($this->options->resize->height)) {
                    $resize_height = $this->options->resize->height;
                }
            } else {
                $resize_width = $image->width();
                $resize_height = $image->height();
            }

            $resize_quality = isset($this->options->quality) ? intval($this->options->quality) : config('voyager.media.upload_image_quality', 75);

            $encoder = $this->getEncoder($file->getClientOriginalExtension(), $resize_quality);
            
            $image = $image->resize($resize_width, $resize_height, function ($constraint) {
                $constraint->aspectRatio();
                if (isset($this->options->upsize) && !$this->options->upsize) {
                    $constraint->upsize();
                }
            })->encode($encoder);

            if ($this->is_animated_gif($file)) {
                Storage::disk(config('voyager.storage.disk'))->put($fullPath, file_get_contents($file), 'public');
                $fullPathStatic = $path.$filename.'-static.'.$file->getClientOriginalExtension();
                Storage::disk(config('voyager.storage.disk'))->put($fullPathStatic, $image->toFilePointer(), 'public');
            } else {
                Storage::disk(config('voyager.storage.disk'))->put($fullPath, $image->toFilePointer(), 'public');
            }

            return $fullPath;
        }
    }

    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param $path
     *
     * @return string
     */
    protected function generateFileName($file, $path)
    {
        if (isset($this->options->preserveFileUploadName) && $this->options->preserveFileUploadName) {
            $filename = basename($file->getClientOriginalName(), '.'.$file->getClientOriginalExtension());
            $filename_counter = 1;

            // Make sure the filename does not exist, if it does make sure to add a number to the end 1, 2, 3, etc...
            while (Storage::disk(config('voyager.storage.disk'))->exists($path.$filename.'.'.$file->getClientOriginalExtension())) {
                $filename = basename($file->getClientOriginalName(), '.'.$file->getClientOriginalExtension()).(string) ($filename_counter++);
            }
        } else {
            $filename = Str::random(20);

            // Make sure the filename does not exist, if it does, just regenerate
            while (Storage::disk(config('voyager.storage.disk'))->exists($path.$filename.'.'.$file->getClientOriginalExtension())) {
                $filename = Str::random(20);
            }
        }

        return $filename;
    }

    private function is_animated_gif($filename)
    {
        $raw = file_get_contents($filename);

        $offset = 0;
        $frames = 0;
        while ($frames < 2) {
            $where1 = strpos($raw, "\x00\x21\xF9\x04", $offset);
            if ($where1 === false) {
                break;
            } else {
                $offset = $where1 + 1;
                $where2 = strpos($raw, "\x00\x2C", $offset);
                if ($where2 === false) {
                    break;
                } else {
                    if ($where1 + 8 == $where2) {
                        $frames++;
                    }
                    $offset = $where2 + 1;
                }
            }
        }

        return $frames > 1;
    }
}
