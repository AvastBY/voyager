<?php

if (!function_exists('is_field_translatable')) {
    /**
     * Check if a Field is translatable.
     *
     * @param Illuminate\Database\Eloquent\Model      $model
     * @param Illuminate\Database\Eloquent\Collection $row
     */
    function is_field_translatable($model, $row)
    {
        if (!is_bread_translatable($model)) {
            return;
        }

        return $model->translatable()
            && method_exists($model, 'getTranslatableAttributes')
            && in_array($row->field, $model->getTranslatableAttributes());
    }
}

if (!function_exists('get_field_translations')) {
    /**
     * Return all field translations.
     *
     * @param Illuminate\Database\Eloquent\Model $model
     * @param string                             $field
     * @param string                             $rowType
     * @param bool                               $stripHtmlTags
     */
    function get_field_translations($model, $field, $rowType = '', $stripHtmlTags = false)
    {
//        $_out = $model->getTranslationsOf($field);
        $_out = $model->getTranslationsOf($field, null, false);
        

        if ($stripHtmlTags && $rowType == 'rich_text_box') {
            foreach ($_out as $language => $value) {
                $_out[$language] = strip_tags($_out[$language]);
            }
        }

        return json_encode($_out);
    }
}

if (!function_exists('is_bread_translatable')) {
    /**
     * Check if BREAD is translatable.
     *
     * @param Illuminate\Database\Eloquent\Model $model
     */
    function is_bread_translatable($model)
    {
        return config('voyager.multilingual.enabled')
            && isset($model)
            && method_exists($model, 'translatable')
            && $model->translatable();
    }
}

if (!function_exists('is_class_field_translatable')) {
	function is_class_field_translatable($className, $field)
	{
		if(!config('voyager.multilingual.enabled')) return false;
		
		if(!is_string($className)) $className = get_class($className);
		
		$model = app($className);
		$reflection = new ReflectionClass($model);
		
		$translatable = false;
		if ($reflection->hasProperty('translatable')) {
			$property = $reflection->getProperty('translatable');
			$property->setAccessible(true);
			
			$translatableArr = $property->getValue($model);
			if (is_array($translatableArr) && in_array($field, $translatableArr)) {
				$translatable = true;
			}
		}

		return $translatable;
	}
}

