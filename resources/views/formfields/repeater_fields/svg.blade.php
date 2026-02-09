<div class="repeater-image-container">
@php
	$src = $source? (isset($source[$key_field])? $source[$key_field] : '' ): '';
@endphp
@if($src)
	<div class="repeater-image-wrap" data-field-name="{{$key_field}}">
		<a href="#" class="voyager-x js-remove-single-image" style="position:absolute;"></a>
		<img src="{{ Voyager::image($src) }}" data-file-name="{{ $src }}" data-id="{{ $row_id }}" class="repeater-image">
	</div>
	<input type="hidden" name="{{$row_field}}_{{$key_field}}_delete_{{$row_id?? '%id%'}}" value="0" class="js-image-del-input">
@endif

<input id="{{$row_field}}_{{$key_field}}_{{$row_id?? '%id%'}}"
    class="adv-form-control form-control voyager-input-file repeater-file-input"
    data-field-type="{{$field->type}}"
    name="{{$row_field}}_{{$key_field}}_{{$row_id?? '%id%'}}"
    type="file"
    accept="{{isset($field->accept)? $field->accept : "image/*" }}"
    @include('voyager::formfields.repeater_fields.attr')
>
</div>