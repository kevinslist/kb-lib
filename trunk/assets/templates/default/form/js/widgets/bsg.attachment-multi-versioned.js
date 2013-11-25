$(document).ready(init_bsg_attachment_multi_versioned_fields);

function init_bsg_attachment_multi_versioned_fields() {
	$('form.bsg-form').submit(bsg_disable_empty_attachment_multi_versioned);
	$('input.bsg-attachment-multi').each(function(){
		var parent = $(this).parent();
		if( $(parent).children('div.MultiFile-list').length > 0){
			$(parent).children('div.MultiFile-list').find('a.MultiFile-remove').click(bsg_multi_attachment_remove)
			var multi_new_list = $(parent).children('div.MultiFile-list');
			var multi_new_list_id = $(multi_new_list).attr('id');
			$(this).MultiFile({list:'#' + multi_new_list_id, afterFileSelect:bsg_multi_attachment_versioned_file_added});
		}else{
			$(this).MultiFile({afterFileSelect:bsg_multi_attachment_versioned_file_added});
		}
	});
	$('div.new-version-upload-wrapper a').click(bsg_multi_attachment_versioned_remove);
	$('fieldset.file-group-wrapper legend a.delete-item').click(bsg_multi_attachment_versioned_remove_file_group)

	$('fieldset.file-group-wrapper table a.delete-attachment-version-item').click(bsg_multi_attachment_versioned_remove_file_version);
}

function bsg_multi_attachment_versioned_file_added(){
	$(window).resize();
}

function bsg_multi_attachment_versioned_remove(){
	var container = $(this).parent().parent().parent();
	$(this).parent().parent().remove();
	$(container).find('div.input-control').show();
	return false;
}

function bsg_multi_attachment_remove(){
	$(this).parent().remove();
	return false;
}

function bsg_disable_empty_attachment_multi_versioned(){
	$('fieldset.bsg-attachment-multi-versioned div.MultiFile-wrap input:last').attr('disabled', true);
	$('fieldset.bsg-attachment-multi-versioned input.new-version-upload').each(function(){
		if('' == $.trim($(this).val())){
			$(this).attr('disabled', true);
		}
	});
	return true;
}

function bsg_multi_attachment_versioned_remove_file_group(){
	$(this).parent().parent().remove();
	return false;
}

function bsg_multi_attachment_versioned_remove_file_version(){
	var id = $(this).attr('data-attachment-id');
	var table_body = $(this).parent().parent().parent();
	var table = $(table_body).parent();
	var old_file_wrapper = $(table).parent();
	var fieldset = $(old_file_wrapper).parent();

	$(this).parent().parent().remove();
	$(old_file_wrapper).find('div#file-info-'+id).remove();
	if( 0 == $(table_body).find('tr').length){
		$(fieldset).remove();
	}
	return false;
}