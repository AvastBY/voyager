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
                    $storedIDs[] = $model->id;
                } else {
                    // Update EXISTED ROWs (or delete)
                    $model = $inlineModel->findOrFail($requestedIDs[$rowIndex]);
                    $model = $this->setModelFields($model, $rowIndex, $rowID);
                    $model->save();
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
            
//            if($field_data->type == 'svg'){
//            	$data = (new SvgContentType($this->request, 'repeater_'.$this->row->field, $dataRow, []))->handle();
//            }else{
//            	$controller = new \Avast\Formfields\Http\Controllers\FormFieldsController();
//				$data = $controller->getContentBasedOnType($this->request, 'repeater_'.$this->row->field, $dataRow);
////            	$data = \TCG\Voyager\Http\Controllers\Controller::getContentBasedOnType($this->request, 'repeater_'.$this->row->field, $dataRow);
//            }
            
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
//        	$model->{$field_name} = \TCG\Voyager\Http\Controllers\Controller::getContentBasedOnType($this->request, 'repeater_'.$this->row->field, $dataRow);
//            if ($field_data->type === 'media') {
//                $model->{$field_name} = $this->row->field . '_' . $field_name . '_' . $rowID;
//            } elseif ($field_data->type === 'checkbox') {
//                $model->{$field_name} = $this->request->input($this->row->field.'_'.$field_name.'_'.$rowID) === 'on'? 1 : 0;
//            } else {
//                $model->{$field_name} = $this->request->input($this->row->field.'_'.$field_name.'_'.$rowID);
//            }
        }
        return $model;
    }

}
