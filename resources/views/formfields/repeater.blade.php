@php
$localStorage = !isset($row->details->repeater->source);

$inlineSource = [];
$itemsIds = array_diff(explode(',', $dataTypeContent->{$row->field}),['']);

if($itemsIds){
    $inlineModel = app($row->details->repeater->source);
    $inlineSource = $inlineModel
    ->where('model', $dataTypeContent->blockModel ?? 'App\\Models\\CompositeBlock')
    ->where('model_id', $dataTypeContent->blockId)
    ->where('model_field', $row->field)
    ->whereIn('id', $itemsIds)->orderByRaw('FIELD(id, '.implode(',', $itemsIds).')')
    ->orderBy('order', 'ASC')
    ->get()->toArray();
}

@endphp

<div class="adv-inline-set-wrapper">
    @if(isset($row->details->repeater->fields))

        <div id="{{ $row->field }}_list" class="adv-inline-set-list"
            data-field="{{ $row->field }}"
            data-deleted=""
            data-many="{{$row->details->repeater->many}}"
            data-local-storage="{{ $localStorage }}">

            <input class="adv-inline-set-row-ids" type="hidden" name="{{ $row->field }}_row_ids"
                   value="{{ implode(',', collect($inlineSource)->map(function ($item, $key) { return $item['row_id']; })->toArray()) }}">
            <input class="adv-inline-set-ids" type="hidden" name="{{ $row->field }}_ids"
                   value="{{ implode(',', collect($inlineSource)->map(function ($item, $key) { return isset($item['id'])? $item['id'] : 0; })->toArray()) }}">
            <input class="adv-inline-set-deleted-ids" type="hidden" name="{{ $row->field }}_deleted_ids" value="">
            <input class="adv-inline-set-deleted-media" type="hidden" name="{{ $row->field }}_deleted_media" value="">

            @if ($inlineSource && count($inlineSource) > 0)
                @foreach($inlineSource as $key => $source)
                    @include('voyager::formfields.repeater_item', [
                        'columns' => isset($row->details->repeater->columns)? $row->details->repeater->columns : 1,
                        'id' => isset($source['id'])? $source['id'] : 0,
                        'row_id' => $source['row_id'],
                        'source' => $source,
                        'block_id' => $dataTypeContent->blockId,
                        'local_storage' => $localStorage,
                        'row_field' => $row->field,
                        'repeater_fields' => $row->details->repeater->fields,
                    ])
                @endforeach
            @endif
        </div>

        @include('voyager::formfields.repeater_item', [
            'columns' => isset($row->details->repeater->columns)? $row->details->repeater->columns : 1,
            'id' => 0,
            'row_id' => null,
            'source' => null,
            'block_id' => $dataTypeContent->blockId,
            'local_storage' => $localStorage,
            'row_field' => $row->field,
            'repeater_fields' => $row->details->repeater->fields,
        ])

        @if ($row->details->repeater->many || !$inlineSource)
            <div class="adv-inline-set-actions">
                <button type="button" class="btn btn-info add-inline-set">
                    Добавить
                </button>
            </div>
        @endif
    @else
        <p>Нет необходимых JSON данных для текущего поля</p>
    @endif
</div>
