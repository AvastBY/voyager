<?php

namespace TCG\Voyager\Http\Controllers\ContentTypes;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Image extends BaseType
{
	protected $manager;

	public function __construct($request, $slug, $row, $options)
	{
		parent::__construct($request, $slug, $row, $options);
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
		}else{
			$file = $this->request->file($this->row->field);
			
			// Если файл есть, но есть ошибка загрузки
			if ($file && $file->getError() !== UPLOAD_ERR_NO_FILE) {
				$errorMessage = $this->getUploadErrorMessage($file->getError());
				throw new \Exception("Ошибка загрузки файла '{$this->row->field}': {$errorMessage}");
			}
		}
		
		return null;
	}
	
	private function getUploadErrorMessage($errorCode)
	{
		$messages = [
			UPLOAD_ERR_INI_SIZE => 'размер файла превышает ' . ini_get('upload_max_filesize'),
			UPLOAD_ERR_FORM_SIZE => 'размер файла превышает ограничение формы',
			UPLOAD_ERR_PARTIAL => 'файл загружен частично',
			UPLOAD_ERR_NO_FILE => 'файл не выбран',
			UPLOAD_ERR_NO_TMP_DIR => 'нет временной папки',
			UPLOAD_ERR_CANT_WRITE => 'ошибка записи на диск',
			UPLOAD_ERR_EXTENSION => 'загрузка остановлена PHP расширением',
		];
		
		return $messages[$errorCode] ?? "неизвестная ошибка (код: {$errorCode})";
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
