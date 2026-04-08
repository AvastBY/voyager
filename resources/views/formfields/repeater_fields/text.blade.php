<input type="text"
       class="adv-form-control form-control"
       id="{{ $field->input_id }}"
       data-field-type="{{$field->type}}"
       name="{{ $field->input_name }}"
       value="{{ $field->value }}"
       @include('voyager::formfields.repeater_fields.attr')>
