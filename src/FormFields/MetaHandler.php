<?php

namespace TCG\Voyager\FormFields;

use TCG\Voyager\FormFields\AbstractHandler;

class MetaHandler extends AbstractHandler
{
	protected $codename = 'meta';

	public function createContent($row, $dataType, $dataTypeContent, $options)
	{
		$meta = json_decode($dataTypeContent->meta);
		if(!$meta) $meta = new \stdClass();
		
		if(empty($meta->title)) $meta->title = null;
		if(empty($meta->description)) $meta->description = null;
		if(empty($meta->keywords)) $meta->keywords = null;
		
		$options->json_field = true;
		
		return view('voyager::formfields.meta',[
			'row' => $row,
			'options' => $options,
			'dataType' => $dataType,
			'dataTypeContent' => $dataTypeContent,
			'meta' => $meta
		]);
	}
}