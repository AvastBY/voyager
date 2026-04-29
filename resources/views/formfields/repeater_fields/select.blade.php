@php
	$options = $field->options ?? false;
	if(!empty($field->options_method)){
		$model = app($sourceModelName);
		$options = $model->projectSelect();
	}
@endphp

@if ($options)
	<select class="form-control adv-form-control select2"
			name="{{$row_field}}_{{$key_field}}_{{$row_id?? '%id%'}}"
			id="{{$row_field}}_{{$key_field}}_{{$row_id?? '%id%'}}"
			data-field-type="{{$field->type}}"
			@include('voyager::formfields.repeater_fields.attr')>
		@if(!empty($field->empty_option))
			<option value="{{ $field->empty_option->key }}">{{ $field->empty_option->value }}</option>
		@endif
		@foreach($options as $value => $label)
			@php
				$default = isset($field->default) && $field->default === $value ? 'selected' : '';
				$selected = isset($source[$key_field]) && $source[$key_field] === $value ? 'selected' : (empty($source[$key_field])? $default : '');
			@endphp
				<option value="{{ $value }}" {{ $selected }}>{{ $label }}</option>
		@endforeach
	</select>
@endif

