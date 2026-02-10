document.addEventListener('change', e => {
	if( e.target.closest('[data-color_input_name]') ) {
		changeColorInputState(e.target.closest('[data-color_input_name]'));
	}
});

function changeColorInputState(cbx){
	var input = cbx.closest('form').querySelector('[name="'+cbx.getAttribute('data-color_input_name')+'"]');
	input.disabled = cbx.checked ? false : true;
}

document.addEventListener('submit', e => {
	if( e.target.closest('._aform') ) {
		let $form = e.target;
		e.preventDefault();

		let click_timeout = 4000;
		if ($form.getAttribute('data-timeout')) {
			click_timeout = +$form.getAttribute('data-timeout');
		}
	
		clearFormErrors($form);
		let $submit = $form.querySelector('[type="submit"], .js-submit');
		if($submit) $submit.classList.add('loading');
	
		var headers = {"X-Requested-With":"XMLHttpRequest"};
		if( document.querySelector('meta[name="csrf-token"]') ) {
			headers['X-CSRF-Token'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
		}
		var fetchFilename = '';
		
		var fd = new FormData($form);
		
		fetch(
			$form.getAttribute('action'),
			{
				method: 'POST',
				body:fd,
				headers: headers
			}
		).then(
			response => {
				if (response.headers.get('content-disposition') && response.headers.get('content-disposition').indexOf("attachment") !== -1) {
					fetchFilename = response.headers.get('content-disposition').split("filename=")[1];
					return response.blob();
				} else {
					if (response.ok) return response.json();
					return response.json().then(response => {
						if (response.errors) {
							var firstError = false;
							for (var key in response.errors) {
								if (!firstError && response.errors[key][0]) firstError = response.errors[key][0];
								if ($form.querySelector('[name="' + key + '"]')) {
									$form.querySelector('[name="' + key + '"]').classList.add('error');
								}
							}
							if(!response.message) {
								return Promise.reject(Object.values(response.errors)[0])
							}
						}
						
						if(response.errors_html) {
							$form.querySelector('._errors-html').style.display = 'block';
							$form.querySelector('._errors-html').innerHTML = response.errors_html;
							return Promise.reject('Есть ошибки')
						}
						
						return Promise.reject(response.message ?? (response.error ?? 'Ошибка'))
					})
				}
			}
		).then(
			response => {
				if(response.constructor.name == 'Blob'){
					var a = document.createElement("a");
					a.href = window.URL.createObjectURL(response);
					a.download = fetchFilename;
					a.click();
					if($submit) $submit.classList.remove('loading');
					return response;
				}
	
				
				if($form.getAttribute('data-ytarget')){
					sendYandexGoal($form.getAttribute('data-ytarget'));
				}
	
				if($form.getAttribute('data-reset') == 1) {
					$form.reset();
				}
				if (response.refresh || $form.getAttribute('data-refresh') == 1) {
					window.location.reload();
				}

				if (response.href) {
					var win = window.location.href = response.href;

				} else if(response.message_html) {
					if ($form.querySelector('._message')) {
						$form.querySelector('._message').innerHTML = response.message_html;
					} else {
						showErrorMessage($form, response.message);
					}
				} else {
					if( $form.getAttribute('data-show-in-popup') ) {
						const $responsePopup = document.querySelector('[data-popup-respose]');
						$responsePopup.classList.add('js-act');
						$responsePopup.querySelector('[data-popup-message]').innerText = response.message;
					} else {
						showSuccessMessage($form, response.message);
					}
					
					if($form.getAttribute('data-redirect')){
						window.location.href = $form.getAttribute('data-redirect');
					}
					setTimeout(function () {
						if ($form.getAttribute('data-click')) {
							document.querySelector($form.getAttribute('data-click')).click()

						} else {
							document.querySelector('.b-popup__close').click();
						}
					}, click_timeout);
				}
				
				if($submit) $submit.classList.remove('loading');
			}
		).catch((error) => {
			if($submit) $submit.classList.remove('loading');
			// alertError.show(error);
			showErrorMessage($form, error);
		});
	}
});

function clearFormErrors($form) {
	let errs = $form.querySelectorAll('._err');
	errs.forEach(function (item, i) {
		item.innerHTML = "";
	});
	
	//убираем класс ошибки с полей
	let err_fields = $form.querySelectorAll('.error');
	err_fields.forEach(function (item, i) {
		item.classList.remove('error');
	});
	if($form.querySelector('._message')){
		$form.querySelector('._message').innerHTML = '';
	}
	
}

function showErrorMessage(message) {
	if(message) toastr.error(message);
	
}

function showSuccessMessage(message) {
	if(message) toastr.success(message);
}

if(document.querySelectorAll('.multiple-images').length){
	document.querySelectorAll('.multiple-images').forEach(function (list, i) {
		Sortable.create(list, {
			handle: '.img_settings_container',
			animation: 150,
			onEnd: function(){
				var field = list.getAttribute('data-id');
				if(window.voyagerUpdateGalleryOrder){ window.voyagerUpdateGalleryOrder(field); }
			}
		})
	});
}

// Drag-and-Drop upload for attributed_gallery
document.addEventListener('click', function(e){
    var dz = e.target.closest('.js-file-dropzone');
    if(!dz) return;
    
    var inputName = dz.getAttribute('data-target-input');
    var form = e.target.closest('form');
    if(form){
		var fileInput = form.querySelector('[data-load-photo="true"][name="'+inputName+'"]');
    }else{
		var fileInput = document.querySelector('[data-load-photo="true"][name="'+inputName+'"]');
    }
    
    if(fileInput){ fileInput.click(); }
});

function handleFilesAdd(files, input){
    console.log(files, input);
    
    var dt = new DataTransfer();
    if(input.files && input.files.length){
        for(var i=0;i<input.files.length;i++){ dt.items.add(input.files[i]); }
    }
    for(var j=0;j<files.length;j++){
        var file = files[j];
        if(file && file.type && file.type.indexOf('image/') === 0){
            dt.items.add(file);
        }
    }
    input.files = dt.files;
    var event = new Event('change');
    input.dispatchEvent(event);
}

document.addEventListener('dragover', function(e){
    var dz = e.target.closest('.js-file-dropzone');
    if(!dz) return;
    e.preventDefault();
    dz.classList.add('is-dragover');
});

document.addEventListener('dragleave', function(e){
    var dz = e.target.closest('.js-file-dropzone');
    if(!dz) return;
    dz.classList.remove('is-dragover');
});

document.addEventListener('drop', function(e){
    var dz = e.target.closest('.js-file-dropzone');
    if(!dz) return;
    e.preventDefault();
    dz.classList.remove('is-dragover');
    var inputName = dz.getAttribute('data-target-input');
    var fileInput = document.querySelector('[data-load-photo="true"][name="'+inputName+'"]');
    if(fileInput){ handleFilesAdd(e.dataTransfer.files, fileInput); }
});

// Keep hidden order field reflecting current list order
window.voyagerUpdateGalleryOrder = function(field){
    var list = document.querySelector('.js-gallery-list[data-id="'+field+'"]');
    var containers = list ? list.querySelectorAll('.img_settings_container') : [];
    var order = [];
    if(containers && containers.length){
        containers.forEach(function(container){
            var img = container.querySelector('img');
            if(img && img.getAttribute('data-image')){
                order.push(img.getAttribute('data-image'));
            } else if(container.classList.contains('is-new')){
                var newIdx = container.getAttribute('data-new-index');
                if(newIdx !== null){ order.push('new_index:'+newIdx); }
            }
        });
    }
    var hidden = document.querySelector('.js-gallery-order[data-id="'+field+'"]');
    if(hidden){ hidden.value = JSON.stringify(order); }
}
