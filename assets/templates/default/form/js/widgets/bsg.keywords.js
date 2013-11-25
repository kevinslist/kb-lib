var bsg_keywords_skip_search		= false;
var bsg_keywords_is_open			= false;
var bsg_keywords_last_search		= '';
var bsg_keywords_last_search_time = 0;
var bsg_keywords_default_text = (undefined != bsg_keywords_default_text) ? bsg_keywords_default_text : "begin typing keyword...";
var bsg_keywords_set_value = true;

$(document).ready(init_bsg_keywords_fields);

function init_bsg_keywords_fields() {
	$('form.bsg-form input.bsg-keywords').each(build_bsg_keywords_lookups);
	$('fieldset.bsg-keywords div.item-list-area').each(function(){
		bsg_zebra_keyword_items($(this));
		$(this).find('div.item-wrapper > a').click(bsg_delete_keyword_item);
	});
}


function build_bsg_keywords_lookups(){
	if(!$(this).hasClass('hidden')){
		var id = $(this).attr('id');
		$(this).autocomplete({
			source: ajax_home + "keywords",
			minLength: 1,
			open:	bsg_keywords_add_css,
			close:	bsg_keywords_indicate_closed,
			search: bsg_keywords_check_search,
			delay:	650,
			width: 300
		}).data( "autocomplete" )._renderItem = function( ul, item ) {
            return $( "<li></li>" )
                .data( "item.autocomplete", item )
                .append( "<a>"+ item.label + "<div class=\"clearfix\"></div></a>" )
                .appendTo( ul );
        };
		$(this).focus(bsg_keywords_receives_focus);
		$(this).blur(bsg_keywords_set_default_text);
		$(this).blur();
		$(this).bind('paste', bsg_keywords_on_paste);
		$(this).bind( "autocompleteselect", bsg_parse_keywords);

	}
}

function bsg_keywords_check_search(){
	var do_search = true;
	bsg_keywords_last_search_time = (new Date()).getTime();
	return do_search;
}
function bsg_keywords_receives_focus(){
	$(this).addClass("has-focus");
	try{
		if( '' != $(this).val ){
			$(this).removeClass('lookup-help');
			if( $(this).val() == bsg_keywords_default_text){
				$(this).val('');
			}else{
				var diff = ((new Date()).getTime() - bsg_keywords_last_search_time) / 10;

				if(diff > 200){
					$(this).autocomplete('search');
				}
			}
		}
	}catch(e){}
}

function bsg_keywords_set_default_text(){
	$(this).removeClass("has-focus");
	if(!bsg_keywords_is_open){
		bsg_keywords_skip_search = false;
	}
	if($.trim($(this).val()) == ''){
		$(this).val(bsg_keywords_default_text);
		$(this).addClass('lookup-help');
	}else{
		$(this).removeClass('lookup-help');
	}

}

function bsg_keywords_add_css(event, ui){
	$('ul.ui-autocomplete.ui-menu').width(300);
	$('ul.ui-autocomplete.ui-menu li.ui-menu-item:odd').addClass('odd');
	bsg_keywords_is_open = true;
}

function bsg_keywords_indicate_closed(){
	bsg_keywords_is_open = false;
}

function bsg_keywords_on_paste(e){
	if(e){
		$(e.target).autocomplete('search');
	}
	return true;
}

function bsg_parse_keywords(event, ui){
	bsg_user_lookup_set_value = true
	try{
		if(ui.item){
			found = true;
			$(this).removeClass('lookup-help');
			bsg_keywords_last_search_time = (new Date()).getTime();
			$(this).trigger('bsg_keywords_parsed', ui.item);
			bsg_keywords_set_value = bsg_add_keyword_item(ui.item, $(this));
			$(this).val('');
		}else{
			bsg_keywords_set_value = false;
		}
	}catch(e){
		alert('error doing keywords lookup'+e);
		bsg_keywords_set_value = false;
	}
	$(this).focus();
	return bsg_keywords_set_value;
}


function bsg_add_keyword_item(item, lookup_field){
	var fieldset = $(lookup_field).parent().parent();
	var list_area = $(fieldset).find('div.item-list-area');
	var new_id = $(fieldset).attr('id') + '-item-' + item.id;

	var existing = $(list_area).find('#' + new_id).length;
	if(0 == existing){
		var clone = $(fieldset).find('div.multi-copy-holder').clone();
		$(clone).removeClass('multi-copy-holder');
		$(clone).attr('id', new_id);
		var input = $(clone).find('input');
		var old_name = $(input).attr('name');
		$(input).attr('name', old_name + '[' + item.id + ']');
		$(input).val(item.value);
		$(input).attr('disabled', false);
		$(clone).find('span.keyword').html(item.value);
		$(list_area).append( $(clone) );
		bsg_zebra_keyword_items($(list_area));

		$(clone).find('a').click(bsg_delete_keyword_item);
		$(clone).show();
	}else{
		alert("Keyword already used");
	}
	return false;
}

function bsg_delete_keyword_item(){
	var list_area = $(this).parent().parent();
	$(this).parent().remove();
	bsg_zebra_keyword_items($(list_area));
	return false;
}


function bsg_zebra_keyword_items(item_area){
	$(item_area).find('div.item-wrapper').removeClass('odd');
	$(item_area).find('div.item-wrapper:odd').addClass('odd');
}