<br>
@php
    $required = ($row->required == 1);
@endphp
@if(isset($dataTypeContent->{$row->field}))
    <?php $images = json_decode($dataTypeContent->{$row->field}); ?>

    @if($images != null)

        @php
            $required = false;
        @endphp
        <div class="multiple-images">
            @foreach($images as $image)
                <div class="img_settings_container" data-field-name="{{ $row->field }}">
                    <img src="{{ Voyager::image( $image->src ) }}" data-image="{{ $image->src }}" data-id="{{ !empty($dataTypeContent->blockId) ? $dataTypeContent->blockId : $dataTypeContent->id }}">
                    <div class="links">
                        <a href="#" class="voyager-params show-inputs"></a>
                        <a href="#" class="voyager-x remove-multi-image-ext"></a>
                    </div>

                    <div class="form-group">
                        <label><b>Alt:</b><input class="form-control" type="text" name="{{ $row->field }}_ext[{{ $loop->index }}][alt]" value="{{ $image->alt }}" autocomplete="off"></label>
                        <label><b>Title:</b><input class="form-control" type="text" name="{{ $row->field }}_ext[{{ $loop->index }}][title]" value="{{ $image->title }}" autocomplete="off"></label>
                        <label><b>Description:</b><input class="form-control" type="text" name="{{ $row->field }}_ext[{{ $loop->index }}][description]" value="{{ $image->description }}" autocomplete="off"></label>
                        <input type="hidden" name="{{ $row->field }}_ext[{{ $loop->index }}][src]" value="{{ $image->src }}">
                    </div>

                </div>
            @endforeach
        </div>
    @endif

@endif
<div class="images-for-upload multiple-images" data-id="{{ $row->field }}"></div>
<div class="v-dropzone js-file-dropzone" data-target-input="{{ $row->field }}[]">
    Перетащите изображения сюда или нажмите, чтобы выбрать
    <small style="display:block;color:#999;margin-top:4px;">Поддерживаются: JPG, PNG, GIF</small>
    <small style="display:block;color:#bbb;">Можно также перетащить несколько файлов сразу</small>
</div>
<div class="clearfix"></div>
<input data-load-photo="true" @if($required) required @endif type="file" name="{{ $row->field }}[]" multiple="multiple" accept="image/*">
<script>

document.addEventListener('DOMContentLoaded', function(){
    if( document.querySelector('[data-load-photo="true"]') ) {
        document.querySelectorAll('[data-load-photo="true"]').forEach( input => {

            input.addEventListener('change', (e) => {

                let files = input.files;
                let name = input.getAttribute('name').replace('[]','');
                let $row = document.querySelector('.images-for-upload[data-id="'+name+'"]');
                $row.innerHTML = '';

                for(var i = 0; i < files.length; i++) {
                    let file = files[i];

                    if ( file.type.startsWith('image/') ){
                        let img = document.createElement('img');
                        img.file = file;
                        $row.insertAdjacentHTML('beforeend', `<div class="img_settings_container" data-fname="`+file.name+`"></div>`);
                        let $container = $row.querySelector('.img_settings_container:last-child');
                        $container.appendChild(img);
                        $container.insertAdjacentHTML('beforeend', `
                                            <div class="links">
                                                <a class="voyager-x remove-image-for-upload"></a>
                                            </div>

                                            <div class="form-group" style="display: block">
                                                <label><b>Alt:</b><input class="form-control" type="text" name="`+name+`_new[`+i+`][alt]" autocomplete="off"></label>
                                                <label><b>Title:</b><input class="form-control" type="text" name="`+name+`_new[`+i+`][title]" autocomplete="off"></label>
                                                <label><b>Description:</b><input class="form-control" type="text" name="`+name+`_new[`+i+`][description]" autocomplete="off"></label>
                                            </div>
                                        `);

                        let reader = new FileReader();
                        reader.onload = (function(aImg) { return function(e) { aImg.src = e.target.result; }; })(img);
                        reader.readAsDataURL(file);
                    };
                }


            });

        });
    };

    document.addEventListener('click', e => {
        let $target = e.target.closest('.remove-image-for-upload');
        if( $target ) {
            let $container = $target.closest('.img_settings_container');
            let fileName = $container.getAttribute('data-fname');
            let $fileInput = document.querySelector('[data-load-photo="true"][name="'+$container.closest('.images-for-upload').getAttribute('data-id')+'[]"]');

            let dt = new DataTransfer();
            for(let i = 0; i < $fileInput.files.length; i++) {
                let file = $fileInput.files[i];
                if(fileName != file.name) dt.items.add($fileInput.files[i]);
            }
            $fileInput.files = dt.files;
            // $container.remove();

            var event = new Event('change');
            $fileInput.dispatchEvent(event);
        }
    });

    // END LOAD FILE IMAGE

    $('.remove-multi-image-ext').on('click', function (e) {
        e.preventDefault();
        $file = $(this).parent().siblings('img');

        params = {
            slug:         '{{ $dataType->slug }}',
            image:        $file.data('image'),
            id:           $file.data('id'),
            field:        $file.parent().data('field-name'),
            multiple_ext: true,
            _token:       '{{ csrf_token() }}'
        }

        $('.confirm_delete_name').text($file.data('image'));
        $('#confirm_delete_modal').modal('show');
    });

    $('#confirm_delete').on('click', function(){
        $.post('{{ ($dataType->slug == 'composite') ?  route($dataType->slug.'.media.remove') : route('voyager.'.$dataType->slug.'.media.remove') }}', params, function (response) {
            if ( response
                && response.data
                && response.data.status
                && response.data.status == 200 ) {
                toastr.success(response.data.message);
                $file.parent().fadeOut(300, function() { $(this).remove(); })
            } else {
                toastr.error("Error removing image.");
            }
        });
        $('#confirm_delete_modal').modal('hide');
    });

    $('.show-inputs').on('click', function (e) {
        e.preventDefault();
        $(this).parent().parent().children('.form-group').toggle();
    });
});
</script>

<style>
.remove-image-for-upload{
    cursor: pointer;
}
.multiple-images{
    display: flex;
    flex-wrap: wrap;
    margin-bottom: 10px;
    width: 100%;
}
.multiple-images .links{
    justify-content: center;
    display: flex;
}
.multiple-images .links a{
    margin: 0 5px;
}
.multiple-images>div{
    display: flex;
    flex-direction: column;
    margin-right: 10px;
}
.images-for-upload img,
.multiple-images img{
    max-width:200px;
    height:auto;
    display:block;
    padding:2px;
    border:1px solid #ddd;
    margin-bottom:5px;
    width: 100%;
}
.vpb-image-group .images-for-upload img,
.vpb-image-group .multiple-images img{
    width: 100%!important;
}

.multiple-images .form-group{
    display: none;
}
.multiple-images label{
    display: block;
}
.multiple-images label b{
    display: inline-block;
    font-size: 10px;
    width: 25px;
}
.multiple-images label input{
    width: 160px;
    display: inline-block;
}
</style>
