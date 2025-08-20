<?php

namespace TCG\Voyager\Http\Controllers\ContentTypes;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttributedGallery extends BaseType
{
    /**
     * @return string
     */
    public function handle()
    {
        $filesData = [];
        if ($this->request->file($this->row->field)){
            $paths = [];
            $files = $this->request->file($this->row->field);

            if (!$files) {
                return;
            }

            foreach ($files as $file) {
                if (!$file->isValid()) {
                    continue;
                }

                $filename = Str::random(20);
                $path = $this->slug.DIRECTORY_SEPARATOR.date('FY').DIRECTORY_SEPARATOR;

                array_push($paths, $path.$filename.'.'.$file->getClientOriginalExtension());
                $filePath = $path.$filename.'.'.$file->getClientOriginalExtension();

                Storage::disk(config('voyager.storage.disk'))->put($filePath, file_get_contents($file), 'public');
            }

            foreach ($paths as $i => $path) {
                $new = $this->request->input($this->row->field.'_new');
                $filesData[$i]['src'] = $path;
                $filesData[$i]['alt'] = empty($new[$i]['alt']) ? '' : $new[$i]['alt'];
                $filesData[$i]['title'] = empty($new[$i]['title']) ? '' : $new[$i]['title'];
                $filesData[$i]['description'] = empty($new[$i]['description']) ? '' : $new[$i]['description'];
            }
        }

        return json_encode($filesData);
    }

}
