$(document).ready(init_multi_building_select_widgets);

function init_multi_building_select_widgets(){
	$('div.field-wrapper.multi_building_room').each(function(){
		$(this).find('div.multi-copy-holder').find('select').attr('disabled', 'disabled');
		$(this).find('div.multi-copy-holder').hide();

		$(this).find('fieldset > div.item-list-area > div.item-wrapper').each(function(){
			$(this).find('a.delete-item').click(bsg_delete_multi_item_row);
			$(this).find('select:first').change(bsg_get_rooms_multi);
		});

		$(this).find('a.add-more-link').click(bsg_add_more_building_room);
		$(this).find('a.add-more-link').click();
		$('form.bsg-form').bind('bsg_validate_form', bsg_remove_empty_buildings)
	});
}

function bsg_remove_empty_buildings(){
	$("div.field-wrapper.multi_building_room .item-list-area .building-wrapper select").each(function(){
		if('' == $.trim($(this).val())){
			$(this).parent().parent().parent().remove();
		}
	});
	//			$('.bsg-form').data('valid-form', false);
}


function bsg_add_more_building_room(anchor){
		var target = anchor.target;
		var container = $(target).parent().parent();

		var clone = $(container).find('div.multi-copy-holder').clone();
		$(clone).removeClass('multi-copy-holder');
		$(clone).find('select').attr('disabled', false);
		var count = $(container).find('div.item-wrapper').length;
		var id = $(container).attr('id') + '-item-' + count;

		$(clone).attr('id', id);
		$(clone).find('select').each(function(){
				$(this).attr('id', $(this).attr('name') + '-new-' + count + '-' + $(this).attr('data-type'));
				$(this).attr('name', $(this).attr('name') + '[new][' + count + ']'+ '[' + $(this).attr('data-type') + ']');
		});

		$(clone).find('a.delete-item').click(bsg_delete_multi_item_row);
		$(clone).find('select:first').change(bsg_get_rooms_multi);
		$(container).find('div.item-list-area').append(clone);
		$(clone).show();
		$(container).find('div.item-wrapper').removeClass('odd');
		$(container).find('div.item-wrapper:even').addClass('odd');

		return false;
}

function bsg_delete_multi_item_row(){
	$(this).parent().remove();
	return false;
}

function bsg_get_rooms_multi(){
	var parent_id = '#' + $(this).parent().parent().parent().attr('id');
	$(parent_id).find('div.room-wrapper > div > select').hide();

	var lookup = ajax_home  + $(this).parents('fieldset.bsg-add-more.multi_building_room').attr('data-room-lookup') + '/' + $(this).val();
	$.ajax(lookup, {context: $(parent_id), dataType: 'json', success:bsg_set_rooms_multi});
}

function bsg_set_rooms_multi(data){
	var room_select = $(this).find('div.room-wrapper > div > select');
	var option = $(room_select).find('option:first').clone();
	$(room_select).empty();
	$(room_select).append(option);

	$.each(data, function(index, value){
		var clone2 = $(option).clone();
		$(clone2).attr("value", value).text(value);
		$(room_select).append(clone2);
	});
	$(room_select).show();
	$(room_select).focus();
}