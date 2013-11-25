var default_lookup_text = (undefined != default_lookup_text) ? default_lookup_text : '';
//"begin typing (lastname, firstname) to perform lookup...";
var bsg_user_lookup_set_value = true;
var bsg_user_lookup_did_init = 'bsg-did-init-userlookup';
$(document).ready(init_bsg_user_lookup_fields);

function init_bsg_user_lookup_fields() {
	$('div.bsg-user-lookup.field-input input.bsg-user-lookup').each(build_bsg_user_lookups);
	$('form.bsg-form').bind('bsg_form_pre_validation', bsg_user_lookup_pre_validation);
	$('form.bsg-form').bind('bsg_form_pre_submit', bsg_user_lookup_pre_submit);

}


function build_bsg_user_lookups(){
	if(!$(this).hasClass('hidden') && !$(this).hasClass(bsg_user_lookup_did_init)){
		var id = $(this).attr('id');
		var temp = $.trim($(this).val());

		$(this).attr({
			'data-uupic':temp
		});

		if( '' != temp ){
			$.ajax({
        url: ajax_home + 'displayname',
				dataType: 'json',
        data: {uupic:temp, id:id},
        context: $(this),
        success: populate_display_name
      });
		}
      filter = $(this).attr('data-bsg-user-lookup-filter');
		$(this).autocomplete({
			source: ajax_home + "userlookup"+"?filter="+filter,
			minLength: 3,
			open:		add_css_userlookup,
			search: bsg_user_lookup_check_search,
			delay:	650
		}).data( "ui-autocomplete" )._renderItem = function( ul, item ) {
            return $( "<li></li>" )
                .data( "item.autocomplete", item )
                .append( "<a class='lookup-item-link'>"+ item.label2 + "</a>" )
                .appendTo( ul );
        };


		$(this).bind( "bsg_user_lookup_set_data", bsg_user_lookup_set_data);
		$(this).bind( "bsg_user_lookup_clear", bsg_user_lookup_clear);
		$(this).bind( "autocompleteselect", parse_user_lookup);
		$(this).bind('paste', user_lookup_on_paste);
		$(this).addClass(bsg_user_lookup_did_init);
		$(this).blur(bsg_user_lookup_blur);
	}
}

/* FORM VALIDATION / TRANSFORMATION OF VALUES */


function bsg_user_lookup_pre_submit(e){
	$('div.bsg-user-lookup.field-input input.bsg-user-lookup').each(function(){
		var uupic = $.trim($(this).attr('data-uupic'));
		$(this).val(uupic);
	});
}

function bsg_user_lookup_pre_validation(e){
	$(e.target).find('div.bsg-user-lookup.field-input input.bsg-user-lookup').each(function(){
		$(this).blur(); // might not be needed but just in case check one more time
	});
}

function bsg_user_lookup_clear(e){
	$(this).val('');
	$(this).attr('data-uupic', '');
	$(this).attr('data-displayname', '');
	$(this).attr('data-emptype', '');
}

function bsg_user_lookup_set_data(e, d){
	$(this).val(d.shortdisplayname);
	$(this).attr('data-emptype', d.emptype);
	$(this).attr('data-uupic', d.uupic);
	$(this).attr('data-displayname',d.shortdisplayname);
}



/* FORM FIELD INTERACTIOn */
function populate_display_name(d){
	if(d.displayname){
		$(this.context).trigger('bsg_user_lookup_set_data', d);
		$(this.context).trigger('bsg_user_parsed', d);
	}
}

function parse_user_lookup(event, ui){
	bsg_user_lookup_set_value = true
	try{
		if(ui.item){
			$(this).trigger('bsg_user_lookup_set_data', ui.item);
			$(this).trigger('bsg_user_parsed', ui.item);
		}else{
			$(this).trigger('bsg_user_lookup_clear');
			$(this).focus();
			bsg_user_lookup_set_value = false;
		}
	}catch(e){
		alert('error doing user lookup: '+e);
		bsg_user_lookup_set_value = false;
	}
  $(window).trigger('bsg_resize_trigger');
	return bsg_user_lookup_set_value;
}

function bsg_user_lookup_blur(){
	var uupic				= $.trim($(this).attr('data-uupic'));
	var displayname = $.trim($(this).attr('data-displayname'));
	var val					= $.trim($(this).val());
	if('' == uupic || displayname != val){
		//var error_message = $(this).parent().parent().find('label span.label-text').text();
		//error_message += ' valid user required.'
		$(this).trigger('bsg_user_lookup_clear');
	}
  $(window).trigger('bsg_resize_trigger');
	return true;
}

function user_lookup_on_paste(e){
	if(e){
		$(e.target).autocomplete('search');
	}
	return true;
}

function add_css_userlookup(event, ui){
	$('ul.ui-autocomplete.ui-menu').width(510);
	$('ul.ui-autocomplete.ui-menu li.ui-menu-item:odd').addClass('odd');
}

function bsg_user_lookup_check_search(){
	var do_search = true;
	if($(this).hasClass('readonly')){
		do_search = false;
	}
	return do_search;
}


