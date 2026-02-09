<div class="color-input-wrapper">
	<input type="checkbox" data-color_input_name="{{ $row->field }}" {{ old($row->field, $dataTypeContent->{$row->field}) ? 'checked' : ''}}>
	<input type="color" class="form-control" name="{{ $row->field }}" value="{{ old($row->field, $dataTypeContent->{$row->field}) }}" {{ old($row->field, $dataTypeContent->{$row->field}) ? '' : 'disabled'}}>
</div>
