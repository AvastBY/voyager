<?php

namespace TCG\Voyager\Http\Controllers\ContentTypes;

use TCG\Voyager\Http\Controllers\ContentTypes\BaseType;

class Meta extends BaseType
{
	public function handle()
	{
		$meta = new \stdClass();
		$meta->title = trim($this->request->input('meta_title'));
		$meta->description = trim($this->request->input('meta_description'));
		$meta->keywords = trim($this->request->input('meta_keywords'));

		if(!$meta->title && !$meta->description && !$meta->keywords) return null;
		
		return json_encode($meta);
    }
}
