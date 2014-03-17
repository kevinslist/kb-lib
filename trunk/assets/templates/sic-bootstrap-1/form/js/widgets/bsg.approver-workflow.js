$(document).ready(init_bsg_approver_workflow);

function init_bsg_approver_workflow(){
	$('div.field-wrapper.approver-workflow div.ajax-load-container').each(function(){
    //log('init baW:' + $(this).attr('data-category-field-id'));
    if($(this).attr('data-category-field-id')){
      $('#' + $(this).attr('data-category-field-id')).bind('change', {container:$(this).parent()}, bsg_approver_workflow_category_changed);
    }
  });

	// loop through approver fields, find those that have been approved and markup accordingly
	bsg_approver_workflow_display_approve_date();

	// as name implies... see function
	bsg_approver_workflow_setup_disposition();


}

function bsg_approver_workflow_category_changed(e){
  var category_id         = $.trim($(this).val());
  if('' != category_id){
    var category_field_id   = $(e.data.container).find('div.ajax-load-container').attr('data-category-field-id');
    var object_class        = $(e.data.container).find('div.ajax-load-container').attr('data-object-class');
    var object_id           = $(e.data.container).find('div.ajax-load-container').attr('data-object-id');
    var field_id            = $(e.data.container).attr('data-field-id');

    $(e.data.container).find('div.ajax-load-container').load(ajax_home + 'get_approver_workflow_by_category/' + field_id + '/' + category_field_id + '/' + category_id + '/' + object_class + '/' + object_id, bsg_approver_workflow_loaded);
  }else{
    $(e.data.container).find('div.ajax-load-container').html('<div></div>')
  }
}

function bsg_approver_workflow_loaded(){
  init_bsg_user_lookup_fields();
}


function bsg_approver_workflow_display_approve_date(){
	// takes the attribute from input field and appends a new div inside field-wrapper
	$('div.field-wrapper.approver-workflow div.field-wrapper.approved').each(function(){
		var div = $('<div class="approved-date" title="Approved Date"></div>');
		var ad = 'Approved: ' + $(this).find('input:first').attr('data-approved-date');
		$(div).html(ad);
		//$(this).prepend( $(div) );
		$(this).find('label').after( $(div) );
		//$(this).append( $(div) );
    if($(this).find('div.container.bsg-user-lookup-multi').length){
      var approver_uupic = $(this).find('div.container.bsg-user-lookup-multi').attr('data-approved-by-uupic');
      $(this).find('div.container.bsg-user-lookup-multi div.item-list-area div.item-wrapper').hide();
      $(this).find('div.container.bsg-user-lookup-multi div.item-list-area div.item-wrapper[data-uupic=' + approver_uupic + ']').show();
    }
	});
	// for looks
	$('fieldset#approvers-section div.field-wrapper:odd').addClass('odd');
}

function bsg_approver_workflow_setup_disposition(){
  $('select.approver-workflow-dispotition-options').each(function(){
    var disposition_wrapper = $('<div id="approver-workflow-disposition-wrapper"><div class="clearfix"></div></div>')
    var form = $(this).parents('form.bsg-form');
    var button_wrapper = $(form).find('.submit-buttons-wrapper:last');
    var button_wrapper_header = $(button_wrapper).find('section.bsg-buttons-wrapper-header');

    $(disposition_wrapper).prepend($(form).find('div.#button-wrapper-0.disposition_routing').remove());
    $(disposition_wrapper).prepend($(this).parent().parent().remove());
    button_wrapper_header.prepend($(disposition_wrapper));
    $(this).change(bsg_approver_workflow_check_disposition);
    bsg_approver_workflow_disable_disposition_button(true);

  });
  /*
	// the submit button section has top/bottom. move disposition button to Top area
	$('.submit-buttons-wrapper:last').prepend();

	// move the disposition drop down next to the disposition submit button
	$('div.#button-wrapper-0.disposition_routing').prepend($('#field-wrapper-approver_workflow_disposition_to').remove());

	// toggle disposistion button if someone selected
	$('select#approver_workflow_disposition_to').change(bsg_approver_workflow_check_disposition);

	// dispable for starters
	bsg_approver_workflow_disable_disposition_button(true);
  */
}

function bsg_approver_workflow_check_disposition(){
	var approver_type_id = $.trim($('select#approver_workflow_disposition_to').val());
	if('' != approver_type_id){
		bsg_approver_workflow_disable_disposition_button(false);
	}else{
		bsg_approver_workflow_disable_disposition_button(true);
	}
}

function bsg_approver_workflow_disable_disposition_button(disabled){
	$('fieldset.buttons-wrapper button.disposition_routing').attr('disabled', disabled);
}