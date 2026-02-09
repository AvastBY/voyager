@if(!$source)<template id="template_{{ $block_id }}_{{$row_field}}">@endif

<div class="adv-inline-set-item columns-{{$columns}} {{ !$source? 'adv-inline-set-template' : '' }}"
	 data-new="{{ !$source? true : false }}"
	 data-id="{{ $id }}"
	 data-row-id="{{ $row_id }}">
	<div class="adv-inline-set-handle">
		<span></span><span></span><span></span>
	</div>
	<div class="adv-inline-set-holder">
		<div class="row">
			@foreach($repeater_fields as $key_field => $field)
				@php
					$fieldIdAttr = implode('_', [$block_id ?? 0, $key_field, $row_field, $row_id?? '%id%'])
				@endphp
				<div class="form-group {{ isset($field->class)? $field->class : 'col-md-12' }}">
					<label class="adv-inline-set-label" for="{{ $fieldIdAttr }}">{{$field->display_name}}</label>
					@if(!empty($field->description))
						<div class="adv-inline-set-description">{!! $field->description !!}</div>
					@endif
						@include('voyager::formfields.repeater_fields.'.$field->type)
				</div>
				@php
					$composite = $row->details->repeater->composite ?? false;
				@endphp
				
				@if($composite)
					@if($source)
						@php
							$sourceModelName = $row->details->repeater->source;
							$sourceModel = $sourceModelName::where('id', $source['id'])->first();
							$sourceDataType = Voyager::model('DataType')->where('model_name', '=',  $sourceModelName)->first();
						@endphp
						<div class="form-group col-md-12">
							<label class="adv-inline-set-label">Контент</label>
							<div>
								<a class="btn btn-info" href="/admin/{{ $sourceDataType->slug }}/{{ $sourceModel->id }}/composite/edit" target="_blank">Перейти к заполнению</a>
							</div>
						</div>
					@else
						<div class="form-group col-md-12">
							<div>
								<p>Для редактирования контента необходимо сохранить блок</p>
							</div>
						</div>
					@endif
				@endif
			@endforeach
		</div>
	</div>
	<button type="button" class="adv-inline-set-delete">
		<i class="voyager-x"></i>
	</button>
</div>
@if(!$source)</template>@endif





