<?php

namespace TCG\Voyager\Http\Controllers;

use Illuminate\Http\Request;
use TCG\Voyager\Models\Thumb;
use Illuminate\Support\Facades\DB;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ThumbsController extends Controller
{
    const SALT = 'A45Scj1381h13ba';

    protected $imageManager;

    public function __construct()
    {
        $this->imageManager = new ImageManager(new Driver());
    }

    // Тумбса иконки
    public function generateThumb($table, $folder, $id, $field, $mark, $filename, $ext)
    {
        if(file_exists(url()->current())) return response()->file(url()->current());

        if(!$table || !$folder || !$id || !$mark || !$filename || !$ext) abort(404);

        $webpHash = self::getHash('webp'.$table.$folder.$id.$mark);
        $hash = self::getHash($ext.$table.$folder.$id.$mark);

        if($hash != $filename && $webpHash != $filename) return abort(404);
        if($webpHash == $filename && $ext != 'webp') $ext = 'png';

        $thumbModel = Thumb::where('mark', $mark)->first();
        if(!$thumbModel) return abort(404);

        $img_path = self::getFieldValue($id, $table, $field);

        return self::createThumbnail($thumbModel, $img_path, $table, $folder, $id, $field, $mark, $filename, $ext);
    }

    // Тумбса галереи
    public static function generateGalleryThumb($table, $folder, $id, $field, $mark, $filename, $ext)
    {
        if(file_exists(url()->current())) return response()->file(url()->current());
        if(!$table || !$folder || !$id || !$field || !$mark || !$filename || !$ext) return abort(404);

        $thumbModel = Thumb::where('mark', $mark)->first();
        if(!$thumbModel) return abort(404);

        $gallery = self::getFieldValue($id, $table, $field);

        $gallery = json_decode($gallery);
        if(!$gallery) return abort(404);

        $img_path = false;
        foreach ($gallery as $key => $it) {
            $src = is_string($it) ? $it : $it->src;
            
            $hash = self::getHash($src.$table.$folder.$id.$mark);

            if($hash != $filename) continue;
            $img_path = $src;
            break;
        }

        if(!$img_path) return abort(404);
        return self::createThumbnail($thumbModel, $img_path, $table, $folder, $id, $field, $mark, $filename, $ext, true);
    }

    public static function getFieldValue($id, $table, $field)
    {
        $model_name = Str::studly(Str::singular($table));
        if($table == 'composites') $model_name = 'CompositeBlock';
        if($model_name && class_exists($model_class = config('voyager.models.namespace').$model_name ?? 'App\\Models\\'.$model_name)){
            $model = $model_class::where('id', (int) $id)->first();
            if(!$model) return abort(404);
            
            if(!empty($model->data) && $model->data->$field){
                $value = $model->data->$field;
            }else{
                $value = $model->$field;
            }
        }else{
            $value = DB::table($table)->where('id', (int) $id)->pluck($field)->first();
        }

        return $value ?? abort(404);
    }

    public static function createThumbnail($thumbModel, $img_path, $table, $folder, $id, $field, $mark, $filename, $ext, $is_gallery = false)
    {
        if(!file_exists(public_path().'/storage/'.$img_path)) abort(404);

        $imageManager = new ImageManager(new Driver());
        $image = $imageManager->read(File::get(public_path().'/storage/'.$img_path));
        
        // Проверяем, что изображение загружено корректно
		if(!$image || $image->width() <= 0 || $image->height() <= 0) {
			abort(400, 'Invalid or corrupted image file');
		}

		$tW = ($thumbModel->width) ? $thumbModel->width : null;
		$tH = ($thumbModel->height) ? $thumbModel->height : null;
		if($tW &&!$tH) $tH = (int) ($image->height()/($image->width()/$tW));
		if($tH &&!$tW) $tW = (int) ($image->width()/($image->height()/$tH));
		
        if($thumbModel->cover){
            $thumbnail = $image->cover($tW, $tH);
        }elseif($thumbModel->fix_canvas){
            $kW = $tW/$image->width();
            $kH = $tH/$image->height();
            $k = $kW;
            if($kH < $kW) $k = $kH;
            
            if($thumbModel->upsize){
				$thumbnail = $image->resize(round($k * $image->width()), round($k * $image->height()));
			}else{
				if($image->width() > $tW || $image->height() > $tH){
					$thumbnail = $image->resize(round($k * $image->width()), round($k * $image->height()));
				}
			}

			if ($thumbModel->canvas_color) {
				$thumbnail = $thumbnail->resizeCanvas($tW, $tH, $thumbModel->canvas_color, 'center');
			}else {
				$canvasColor = 'eee';
				if($ext == 'png') $canvasColor = 'rgba(0, 0, 0, 0)';
				$thumbnail = $thumbnail->resizeCanvas($tW, $tH, $canvasColor, 'center');
			}
		}else{
			$kW = $tW/$image->width();
			$kH = $tH/$image->height();
			$k = $kW;
			if($kH < $kW) $k = $kH;
			
			if($thumbModel->upsize){
				$thumbnail = $image->resize(round($k * $image->width()), round($k * $image->height()));
			}else{
				if($image->width() > $tW || $image->height() > $tH){
					$thumbnail = $image->resize(round($k * $image->width()), round($k * $image->height()));
				}
			}
        }

        if($thumbModel->blur > 0) {
            $blurValue = $thumbModel->blur <= 100 ? $thumbModel->blur : 100;
            $thumbnail = $thumbnail->blur($blurValue);
        }

        if($ext == 'jpeg') $ext = 'jpg';
        
        if($is_gallery){
            $path = '_thumbs/'.$table.'/'.$folder.'/'.$id.'/gallery/'.$field.'/'.$mark.'/'.$filename.'.'.$ext;
        }else{
            $path = '_thumbs/'.$table.'/'.$folder.'/'.$id.'/'.$field.'/'.$mark.'/'.$filename.'.'.$ext;
        }

        $quality = $thumbModel->quality ?? 90;
		$encoded = $thumbnail->encodeByExtension($ext, $quality);
		
        Storage::disk('public')->put($path, $encoded);

        return response($encoded)->header('Content-Type', 'image/'.$ext);
    }

    public static function getHash($str)
    {
        $hash = crypt(md5($str), env('THUMBS_SALT') ?? self::SALT);
        $hash = str_replace(array('.','/',',','?',''), 'x', $hash);

        return $hash;
    }

    public function generatePlaceholder($mark, $ext)
    {
        if (file_exists(url()->current())) return url()->current();

        if (!$mark || !$ext) return abort(404);

        $thumbModel = Thumb::where('mark', $mark)->first();

        if (!$thumbModel) return abort(404);

        $tW = ($thumbModel->width) ? $thumbModel->width : null;
        $tH = ($thumbModel->height) ? $thumbModel->height : null;

        if(!$tW && !$tH) return abort(404);

        if(!$tH) $tH = $tW;
        if(!$tW) $tW = $tH;

        $imageManager = new ImageManager(new Driver());
        $image = $imageManager->create($tW, $tH)->fill('#eee');

        $path = '_thumbs/placeholders/'.$mark.'.jpg';
        Storage::disk('public')->put($path, $image->toJpeg());

        return response($image->toJpeg())->header('Content-Type', 'image/jpeg');
    }
}
