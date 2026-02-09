@php
	if(is_field_translatable($dataTypeContent, $row)){
		$metaTranslations = json_decode(get_field_translations($dataTypeContent, $row->field));
		$metaTitle = $metaDescription = $metaKeywords = [];
		if($metaTranslations){
			foreach ($metaTranslations as $key => $metaTranslation){
				$data = json_decode($metaTranslation,1);
				$metaTitle[$key] = $data['title'] ?? '';
				$metaDescription[$key] = $data['description'] ?? '';
				$metaKeywords[$key] = $data['keywords'] ?? '';
			}
		}
		$metaTitle_i18n = json_encode($metaTitle);
		$metaDescription_i18n = json_encode($metaDescription);
		$metaKeywords_i18n = json_encode($metaKeywords);
	}
@endphp

<div style="background-color: #f6f6f6; padding: 10px 5px;border: 1px solid #e4eaec;">
	<div class="form-group  col-md-12 ">
		<label class="control-label">Title</label>
		@if (is_field_translatable($dataTypeContent, $row))
			<input type="hidden" data-i18n="true" name="meta_title_i18n" id="meta_title_i18n" value="{{ $metaTitle_i18n }}">
		@endif
		<input type="text" class="form-control" name="meta_title" value="{{ $meta->title }}">
	</div>
	<div class="form-group  col-md-12 ">
		<label class="control-label">Description</label>
		@if (is_field_translatable($dataTypeContent, $row))
			<input type="hidden" data-i18n="true" name="meta_description_i18n" id="meta_description_i18n" value="{{ $metaDescription_i18n }}">
		@endif
		<textarea class="form-control" name="meta_description">{{ $meta->description }}</textarea>
	</div>
	<div class="form-group  col-md-12 ">
		<label class="control-label">Keywords</label>
		@if (is_field_translatable($dataTypeContent, $row))
			<input type="hidden" data-i18n="true" name="meta_keywords_i18n" id="meta_keywords_i18n" value="{{ $metaKeywords_i18n }}">
		@endif
		<input type="text" class="form-control" name="meta_keywords" value="{{ $meta->keywords }}">
	</div>
	<div style="clear: both"></div>
</div>