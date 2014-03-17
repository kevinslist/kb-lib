var default_lookup_text = (undefined != default_lookup_text) ? default_lookup_text : '';
//"begin typing (lastname, firstname) to perform lookup...";
var bsg_lookup_set_value = true;
var bsg_lookup_did_init = 'bsg-did-init-lookup';
$(document).ready(bsg_lookup_widget_ready);

function bsg_lookup_widget_ready(){
	$('form.bsg-form').bind('bsg_form_pre_validation', bsg_lookup_pre_validation);
	$('form.bsg-form').bind('bsg_form_pre_submit', bsg_lookup_pre_submit);
  $('form.bsg-form').bind('bsg_one_to_many_cloned', init_bsg_lookup_fields);
  init_bsg_lookup_fields();
}

function init_bsg_lookup_fields() {
	$('div.bsg-lookup.field-input input.bsg-lookup').each(build_bsg_lookups);
}

function build_bsg_lookups(){
	if(
          $(this).is(':visible')
          && !$(this).hasClass('hidden') 
          && !$(this).hasClass(bsg_lookup_did_init)
      ){
		var id = $(this).attr('id');
		var temp = $.trim($(this).val());

		$(this).attr({
			'data-actual':temp
		});

    $(this).bind('init_bsg_lookup_field_populate', init_bsg_lookup_field_populate);
    $(this).trigger('init_bsg_lookup_field_populate');
/*
		if( '' != temp ){
			$.ajax({
        url: ajax_home + $(this).attr('populate'),
				dataType: 'json',
        data: {actual:temp, id:id},
        context: $(this),
        success: populate_item
      });
		}
*/
		$(this).autocomplete({
			source: ajax_home + $(this).attr('lookup'),
			minLength: 3,
			open:		add_css_lookup,
			search: bsg_lookup_check_search,
			delay:	650
		}).data( "autocomplete" )._renderItem = function( ul, item ) {
            return $( "<li></li>" )
                .data( "item.autocomplete", item )
                .append( "<a>"+ item.label + "<div class=\"clearfix\"></div></a>" )
                .appendTo( ul );
        };


		$(this).bind( "bsg_lookup_set_data", bsg_lookup_set_data);
		$(this).bind( "bsg_lookup_clear", bsg_lookup_clear);
		$(this).bind( "autocompleteselect", parse_lookup);
		$(this).bind('paste', lookup_on_paste);
		$(this).addClass(bsg_lookup_did_init);
		$(this).blur(bsg_lookup_blur);
	}
}

function init_bsg_lookup_field_populate(){

  var temp = $.trim($(this).val());
		var id = $(this).attr('id');
		if( '' != temp ){
			$.ajax({
        url: ajax_home + $(this).attr('populate'),
				dataType: 'json',
        data: {actual:temp, id:id},
        context: $(this),
        success: populate_item
      });
		}
}

/* FORM VALIDATION / TRANSFORMATION OF VALUES */


function bsg_lookup_pre_submit(e){
	$('div.bsg-lookup.field-input input.bsg-lookup').each(function(){
		var actual = $.trim($(this).attr('data-actual'));
		$(this).val(actual);
	});
}

function bsg_lookup_pre_validation(e){
	$(e.target).find('div.bsg-lookup.field-input input.bsg-lookup').each(function(){
		$(this).blur(); // might not be needed but just in case check one more time
	});
}

function bsg_lookup_clear(e){
	$(this).val('');
	$(this).attr('data-actual', '');
	$(this).attr('data-display', '');
}

function bsg_lookup_set_data(e, d){
	$(this).val(d.display);
	$(this).attr('data-actual', d.actual);
	$(this).attr('data-display',d.display);
}



/* FORM FIELD INTERACTIOn */
function populate_item(d){
	if(d.display){
		$(this.context).trigger('bsg_lookup_set_data', d);
		$(this.context).trigger('bsg_parsed', d);
	}
}

function parse_lookup(event, ui){
	bsg_lookup_set_value = true
	try{
		if(ui.item){
			$(this).trigger('bsg_lookup_set_data', ui.item);
			$(this).trigger('bsg_parsed', ui.item);
		}else{
			$(this).trigger('bsg_lookup_clear');
			$(this).focus();
			bsg_lookup_set_value = false;
		}
	}catch(e){
		alert('error doing  lookup: '+e);
		bsg_lookup_set_value = false;
	}
	return bsg_lookup_set_value;
}

function bsg_lookup_blur(){
	var actual				= $.trim($(this).attr('data-actual'));
	var display = $.trim($(this).attr('data-display'));
	var val					= $.trim($(this).val());
	if('' == actual || display != val){
		//var error_message = $(this).parent().parent().find('label span.label-text').text();
		//error_message += ' valid  required.'
		$(this).trigger('bsg_lookup_clear');
	}
	return true;
}

function lookup_on_paste(e){
	if(e){
		$(e.target).autocomplete('search');
	}
	return true;
}

function add_css_lookup(event, ui){
  
	$('ul.ui-autocomplete.ui-menu').width(510);
	$('ul.ui-autocomplete.ui-menu li.ui-menu-item:odd').addClass('odd');
}

function bsg_lookup_check_search(){
	var do_search = true;
	if($(this).hasClass('readonly')){
		do_search = false;
	}
	return do_search;
}


