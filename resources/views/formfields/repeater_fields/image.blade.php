{{--@if(isset($dataTypeContent->{$row->field}))--}}
{{--    <div data-field-name="{{ $row->field }}">--}}
{{--        <a href="#" class="voyager-x remove-single-image" style="position:absolute;"></a>--}}
{{--        <img src="@if( !filter_var($dataTypeContent->{$row->field}, FILTER_VALIDATE_URL)){{ Voyager::image( $dataTypeContent->{$row->field} ) }}@else{{ $dataTypeContent->{$row->field} }}@endif"--}}
{{--          data-file-name="{{ $dataTypeContent->{$row->field} }}" data-id="{{ $dataTypeContent->getKey() }}"--}}
{{--          style="max-width:200px; height:auto; clear:both; display:block; padding:2px; border:1px solid #ddd; margin-bottom:10px;">--}}
{{--    </div>--}}
{{--@endif--}}
{{--<input @if($row->required == 1 && !isset($dataTypeContent->{$row->field})) required @endif type="file" name="{{ $row->field }}" accept="image/*">--}}
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