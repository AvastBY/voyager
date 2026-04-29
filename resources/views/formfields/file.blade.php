@if(isset($dataTypeContent->{$row->field}))
	@if(json_decode($dataTypeContent->{$row->field}) !== null)
		@foreach(json_decode($dataTypeContent->{$row->field}) as $file)
			<div data-field-name="{{ $row->field }}">
				@if(!empty($file->download_link) && in_array(pathinfo($file->download_link, PATHINFO_EXTENSION), ['png', 'jpg', 'jpeg', 'webp', 'gif']))
					<div class="repeater-file-image-wrap">
						<img src="{{ Voyager::image($file->download_link) }}" class="repeater-file-image">
					</div>
				@endif
				<a class="fileType" target="_blank"
					href="{{ Storage::disk(config('voyager.storage.disk'))->url($file->download_link) ?: '' }}"
					data-file-name="{{ $file->original_name }}" data-id="{{ $dataTypeContent->getKey() }}">
					{{ $file->original_name ?: '' }}
				</a>
				<a href="#" class="voyager-x remove-multi-file"></a>
			</div>
		@endforeach
	@else
		<div data-field-name="{{ $row->field }}"></div>
	@endif
@endif
<input @if($row->required == 1 && !isset($dataTypeContent->{$row->field})) required @endif type="file" name="{{ $row->field }}[]" multiple="multiple">
