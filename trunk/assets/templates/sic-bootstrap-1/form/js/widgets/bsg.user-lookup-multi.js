$(document).ready(init_bsg_user_lookup_multi_fields);

function init_bsg_user_lookup_multi_fields() {
	$('.bsg-user-lookup-multi input.bsg-user-lookup-multi').each(function(){
    $(this).bind('bsg_user_parsed', bsg_user_lookup_multi_selected);
    $(this).attr('required', false);
    $(this).removeClass('required');
  });
  
	$('.bsg-user-lookup-multi div.item-list-area').each(function(){
		bsg_zebra_multi_items($(this));
		$(this).find('div.item-wrapper > a').click(bsg_user_lookup_multi_delete_item);
	});
}


function bsg_user_lookup_multi_selected(o, person){
	try{
		if( undefined != person.uupic){
			var lookup_field = $(o.target);
			var fieldset = $(lookup_field).parent().parent();
      var original_id = $(fieldset).attr('data-id');
      var escaped_id = original_id.replace(/([\[\]])/g, '\\\$1');

			var search_id		= escaped_id + '_' + person.uupic;
			var new_id		= original_id + '_' + person.uupic;
      
			if( $('#'+search_id).length == 0){
				var clone = $(fieldset).find('div.multi-copy-holder').clone();
				$(clone).removeClass('multi-copy-holder');
				var input = $(clone).find('input');
				$(input).attr('name', $(input).attr('name') + '[' + person.uupic + ']');
				$(input).attr('id', new_id);
				$(input).attr('disabled', false);
				$(input).val(person.uupic);
				$(clone).find('span').text(person.shortdisplayname);
				$(fieldset).find('div.item-list-area').append(clone);
				$(clone).find('a').click(bsg_user_lookup_multi_delete_item);
				bsg_user_lookup_set_value = false;
				$(lookup_field).val('');
				$(lookup_field).blur();
				bsg_zebra_multi_items($(fieldset).find('div.item-list-area'));
				$(lookup_field).focus();
			}else{
				alert('User already selected');
			}
		}
	}catch(exception){}
}

function bsg_zebra_multi_items(item_area){
	$(item_area).find('div.item-wrapper').removeClass('odd');
	$(item_area).find('div.item-wrapper:odd').addClass('odd');
}

function bsg_user_lookup_multi_delete_item(){
	var item_area = $(this).parent().parent();
	$(this).parent().remove();
	bsg_zebra_multi_items(item_area);
	return false;
}