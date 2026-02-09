<div class="repeater-image-container">
    @php
        $data = $source ? (isset($source[$key_field]) && isset($source[$key_field])? json_decode($source[$key_field]) : '' ): '';
        $file = false;
        if(!empty($data[0])) $file = $data[0];
    @endphp
    
    @if($file)
        <div data-field-name="{{$key_field}}" style="margin-bottom: 10px">
            <a class="fileType" target="_blank" href="{{ Storage::disk(config('voyager.storage.disk'))->url($file->download_link) ?: '' }}" data-file-name="{{ $file->original_name }}" data-id="{{ $dataTypeContent->getKey() }}">
                {{ $file->original_name ?: '' }}
            </a>
            <a href="#" class="voyager-x remove-multi-file"></a>
        </div>
    @endif
    
    <input id="{{$row_field}}_{{$key_field}}_{{$row_id?? '%id%'}}"
        class="adv-form-control form-control voyager-input-file repeater-file-input"
        data-field-type="{{$field->type}}"
        name="{{$row_field}}_{{$key_field}}_{{$row_id?? '%id%'}}"
        type="file"
        accept="{{isset($field->accept)? $field->accept : '' }}"
        @include('voyager::formfields.repeater_fields.attr')
    >
</div>
