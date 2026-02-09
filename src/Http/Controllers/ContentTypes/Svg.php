<?php

namespace TCG\Voyager\Http\Controllers\ContentTypes;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image as InterventionImage;
use TCG\Voyager\Http\Controllers\ContentTypes\BaseType;

class Svg extends BaseType
{
    public function handle()
    {
    	
        if ($this->request->hasFile($this->row->field)) {
            $file = $this->request->file($this->row->field);

            $path = $this->slug.DIRECTORY_SEPARATOR.date('FY').DIRECTORY_SEPARATOR;

            $filename = $this->generateFileName($file, $path);

            $fullPath = $path.$filename.'.'.$file->getClientOriginalExtension();
            Storage::disk(config('voyager.storage.disk'))->put($fullPath, file_get_contents($file), 'public');
            return $fullPath;
        }
        
        return $this->request->input($this->row->field.'_uploaded');
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
