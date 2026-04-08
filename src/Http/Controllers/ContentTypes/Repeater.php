<?php

namespace TCG\Voyager\Http\Controllers\ContentTypes;

use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\ContentTypes\BaseType;
use TCG\Voyager\Models\DataRow;

class Repeater extends BaseType
{
    private $model_name;
    private $model_id;

    public function handle()
    {
        $this->model_name = $this->request->model_name;
        $this->model_id = $this->request->model_id;
        $inlineModel = isset($this->options->repeater->source)? app($this->options->repeater->source) : null;
        
        $requestedRowIDs = explode(',', $this->request->input($this->row->field.'_row_ids'));
        $requestedIDs = explode(',', $this->request->input($this->row->field.'_ids'));
        $requestedDeletedIDs = explode(',', $this->request->input($this->row->field.'_deleted_ids'));

        // Remove deleted Rows
        if (count($requestedDeletedIDs) > 0 && !empty($requestedDeletedIDs[0])) {
            foreach ($requestedDeletedIDs as $deletedID) {
                $model = $inlineModel->findOrFail($deletedID);
                $model->delete();
            }
        }

        if (count($requestedRowIDs) === 0 || empty($requestedRowIDs[0])) {
            return null;
        }
        // ----------------------------------------------
        // Store inline set in the related storage model
        // ----------------------------------------------
        if (isset($this->options->repeater->source)) {
            $inlineModel = app($this->options->repeater->source);
            $storedIDs = [];
            foreach ($requestedRowIDs as $rowIndex => $rowID) {
                if ((int)$requestedIDs[$rowIndex] === 0) {
                    // Create a NEW ROW
                    $model = new $inlineModel;
                    $model->model = $this->model_name;
                    $model->model_id = $this->model_id;
                    $model->model_field = $this->row->field;
                    $model->row_id = $rowID;
                    $model = $this->setModelFields($model, $rowIndex, $rowID);
                    $model->save();
                    
                    $this->saveModelTranslations($model, $rowIndex, $rowID);
                    
                    $storedIDs[] = $model->id;
                } else {
                    // Update EXISTED ROWs (or delete)
                    $model = $inlineModel->findOrFail($requestedIDs[$rowIndex]);
                    $model = $this->setModelFields($model, $rowIndex, $rowID);
                    $model->save();
                    
                    $this->saveModelTranslations($model, $rowIndex, $rowID);
                    
                    $storedIDs[] = $model->id;
                }
            }

            return implode(',', $storedIDs);

        // ----------------------------------------------
        // Store inline set in the local field
        // ----------------------------------------------
        } else {
            $inlineRows = [];
            foreach ($requestedRowIDs as $rowIndex => $rowID) {
                $model = (object)[];
                $model = $this->setModelFields($model, $rowIndex, $rowID);
                $inlineRows[] = $model;
            }
            return json_encode($inlineRows);
        }
    }

	private function setModelFields($model, $rowIndex, $rowID = null)
	{
		$model->row_id = $rowID;
		$model->order = $rowIndex;
		foreach ($this->options->repeater->fields as $field_name => $field_data) {
		
			$dataRow = new DataRow();
			$dataRow->field = $this->row->field.'_'.$field_name.'_'.$rowID;
			$dataRow->display_name = $field_data->label ?? $field_data->display_name;
			$dataRow->type = $field_data->type;
			$dataRow->required = $field_data->required ?? 0;
			$dataRow->details = $field_data->details ?? null;
			$dataRow->placeholder = $field_data->placeholder ?? 0;
			
			if(is_class_field_translatable($model, $field_name)){
				$defaultLang = config('voyager.multilingual.default');
				$dataRow->field = $dataRow->field.'.'.$defaultLang;
			}
			
			$controller = new \TCG\Voyager\Http\Controllers\VoyagerBaseController();
			$data = $controller->getContentBasedOnType($this->request, 'repeater_'.$this->row->field, $dataRow);
			
			if(in_array($field_data->type, ['image', 'svg', 'file'])){
				if($data){
					$model->{$field_name} = $data;
				}else if($this->request->input($this->row->field.'_'.$field_name.'_delete_'.$rowID) == 1){
					$model->{$field_name} = '';
				}
			}else{
				$model->{$field_name} = $data;
			}
		}
		
		return $model;
	}
	
	private function saveModelTranslations($model, $rowIndex, $rowID = null){
		if(!empty($field_data->translatable) && config('voyager.multilingual.enabled')) return false;
		
		$model->row_id = $rowID;
		$model->order = $rowIndex;
		foreach ($this->options->repeater->fields as $field_name => $field_data) {
		
			$dataRow = new DataRow();
			$dataRow->field = $this->row->field.'_'.$field_name.'_'.$rowID;
			$dataRow->display_name = $field_data->label ?? $field_data->display_name;
			$dataRow->type = $field_data->type;
			$dataRow->required = $field_data->required ?? 0;
			$dataRow->details = $field_data->details ?? null;
			$dataRow->placeholder = $field_data->placeholder ?? 0;
			
			$controller = new \TCG\Voyager\Http\Controllers\VoyagerBaseController();
			
			if(is_class_field_translatable($model, $field_name)){
				$defaultLang = config('voyager.multilingual.default');
				$langs = config('voyager.multilingual.locales');
				
				$langData = [];
				foreach ($langs as $lang) {
					if($lang == $defaultLang) continue;
					
					$_dataRow = clone($dataRow);
					$_dataRow->field = $dataRow->field.'.'.$lang;
					$langData[$lang] = $controller->getContentBasedOnType($this->request, 'repeater_'.$this->row->field, $_dataRow);
				}
				
				if($langData){
					foreach ($langData as $lang => $value) {
						$translation = Voyager::model('Translation')->where([
							'locale' => $lang,
							'table_name' => $model->getTable(),
							'foreign_key' => $model->id,
							'column_name' => $field_name,
						])->first();
							
						if($value){
							if(!$translation) $translation = Voyager::model('Translation');
							
							$translation->locale = $lang;
							$translation->table_name = $model->getTable();
							$translation->foreign_key = $model->id;
							$translation->column_name = $field_name;
							$translation->value = $value;
							
							$translation->save();
						}else{
							if($translation) $translation->delete();
						}
					}
				}
			}
		}
		
		return $model;
	}

}
