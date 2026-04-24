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

	public function handle()
	{
		if ($this->request->hasFile($this->row->field)) {
			$file = $this->request->file($this->row->field);
	
			$path = $this->slug.DIRECTORY_SEPARATOR.date('FY').DIRECTORY_SEPARATOR;
	
			$filename = $this->generateFileName($file, $path);
	
			$fullPath = $path.$filename.'.'.$file->getClientOriginalExtension();
	
			// Сохраняем файл как есть, без всякой обработки
			Storage::disk(config('voyager.storage.disk'))->put(
				$fullPath, 
				file_get_contents($file), 
				'public'
			);
	
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
}
