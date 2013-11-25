$(document).ready(init_kb_form_global);

function init_kb_form_global(){

  $('form.kb-form .submit-buttons-wrapper button').click(kb_form_trigger_submit_clicked);
  $("form.kb-form").keypress(kb_check_form_keypress_submit);
  $('form.kb-form').submit(kb_form_submit);
  $('form.kb-form').bind('kb_validate_form', kb_form_global_validate);
  $('form.kb-form').bind('kb_form_field_error', kb_form_report_error);
  $('form.kb-form').bind('kb_form_validation_failed', kb_form_validation_failed);

  $('form.kb-form').bind('kb_one_to_many_cloned_animation_complete', kb_form_new_fields_added);
  kb_form_new_fields_added();
  kb_form_fix_readonly();
  kb_init_collapsible_fieldsets();
}


function kb_form_fix_readonly(){
  $('input.kb-user-input').each(function(){
    if('text' == $(this).prop('type')){
      var user_lookup = $(this).hasClass('kb-user-lookup');
      var kb_date = $(this).hasClass('kb-date');
      if(!kb_date && !user_lookup){
        var readonly_value = $(this).attr('data-readonly-value') !== undefined;
        var val = readonly_value ? $(this).attr('data-readonly-value') : $(this).val();
        $(this).parent().append('<div class="kb-readonly-text">'+val+'</div>');
        if($(this).hasClass('readonly')){
          $(this).hide();
        }
      }
    }
  });

}

function kb_form_trigger_submit_clicked(e){
  $("form.kb-form .submit-buttons-wrapper div.button-wrapper button").removeClass('kb-button-clicked');
  $(this).addClass('kb-button-clicked');
  var submit_type = $.trim($(this).attr('data-value'));
  $(this).prop('data-will-fire', true);
  var event_data = {
    'button':$(this),
    'type':submit_type
  };
  var the_form = $(this).parents('form.kb-form');
  var confirm_message = $.trim($(this).attr('data-confirm-message'));
  var did_confirm = true;
  var will_fire = true;

  if(confirm_message != ''){
    did_confirm = confirm(confirm_message);
  }
  if(did_confirm){
    if('cancel' == submit_type || 'delete' == submit_type){
      $(the_form).find('.required').each(function(){
        $(this).removeClass('required');
        $(this).prop('required', false);
        $(the_form).attr('data-is-cancel', true);
      });
    }else{
      $(the_form).trigger('kb_validate_form');
    }

    $(the_form).trigger('kb_form_submit_clicked', event_data);
    will_fire = $(this).prop('data-will-fire');
  }else{
    will_fire = false;
  }

  if(!will_fire){
    e.preventDefault();
  }
  return will_fire;
}


function kb_form_submit(e){
  kb_hide_submit_buttons();
  try{
    $(this).trigger('kb_form_pre_validation');

    var form_errors = '';
    var is_valid_form = true;

    if(!$(this).attr('data-is-cancel')){
      $(this).trigger('kb_validate_form');
      form_errors = $(this).data('form-errors');
      is_valid_form = $(form_errors).length == 0;
      $(this).trigger('kb_form_post_validation');
      if(is_valid_form){
        $(this).trigger('kb_form_post_validation_success');
      }
    }

    if(is_valid_form){
      var button = $(this).find('.submit-buttons-wrapper').find('button.kb-button-clicked');
      $(button).val($(button).attr('data-value'));
      $(button).text($(button).attr('data-value'));
    }else{
      $(this).trigger('kb_form_validation_failed');
      kb_show_submit_buttons();
    }
  }catch(e){
    alert('catch' + e);
    is_valid_form = false;
    kb_show_submit_buttons();
  }

  if(is_valid_form){
    // "garaunteed" to submit form, do what's needed to widget data prior to submission'
    $(this).trigger('kb_form_pre_submit');
  }
  return is_valid_form;
}

function kb_check_form_keypress_submit(e){
  if (e.keyCode == 13){
    if($(e.target).hasClass('textarea') || $(e.target).hasClass('submit-button')){
      return true;
    }else{
      var submit_on_enter = $(e.target).attr('data-kb-input-submit-on-enter');
      log('submit_on_enter:' + submit_on_enter);
      return submit_on_enter ? true : false;
    }
  }
}

function kb_form_init_per_field_validation(){
  $('.kb-user-input:visible').each(function(){
    if(!$(this).hasClass('kb_form_init_per_field_validation')){
      $(this).change(kb_form_global_validate);
      $(this).addClass('kb_form_init_per_field_validation');
    }
  });
}

function kb_form_global_validate(e){
  var error_message;
  if( $(e.target).hasClass('kb-user-input')){
    kb_form_validate_field($(e.target));
  }else{
    $(this).addClass('kb-did-submit-for-validation');
    $(this).data('form-errors', new Array());
    $(e.target).find('.kb-user-input').each(function(){
      kb_form_validate_field($(this));
    });
  }
}

function kb_form_validate_field(the_field){
  var the_form = $(the_field).parents('form.kb-form');
  if($(the_form).hasClass('kb-did-submit-for-validation')){
    var field_value = $.trim($(the_field).val());
    var field_wrapper = $(the_field).parents('.field-wrapper:first');
    var label = $.trim($(field_wrapper).find('label > span.label-text').text());

    if($(the_field).attr('required') && '' == field_value){
      error_message = ('' == label ? '' : label + ': ') + 'field required';
      $(the_field).attr('data-error-message', error_message);
      $(field_wrapper).addClass('error');
      $(the_form).trigger('kb_form_field_error', $(the_field).attr('id'));
    }else{
      $(field_wrapper).removeClass('error');
    }
  }
}


function kb_form_report_error(e, i){
  var form_errors = $(this).data('form-errors');
  if(undefined !== $(this).data('form-errors')){
    form_errors.push(i);
    $(this).data('form-errors',form_errors);
  }
}

function kb_form_validation_failed(e){
  var form_errors = $(e.target).data('form-errors');
  var error_message = '';
  var did_focus = false;
  $(form_errors).each(function(index, value){

    var field_id = value.replace(/([\[\]])/g, '\\\$1');
    var field = $(field_id).length ? $(value) : $('#' + field_id);
    error_message += '<p>' + $(field).attr('data-error-message') + "</p>";
    if(!did_focus){
      did_focus = true;
      var offset = $(field).offset();
      $(window).scrollTop(offset.top - 40);
      $(field).focus();
    }
  });
  var d_id = '#' + $(e.target).attr('id') + '-form-errors-dialog';
  var dialog_wrapper = $(d_id);

  /*
	var title = $(e.target).find('legend.form-title').text() + ' Error!';
	$(dialog_wrapper).dialog('destroy');

	$(dialog_wrapper).dialog({
		height: 'auto',
		modal: true,
		width: 800,
		title: title
	});
  */
  //$(dialog_wrapper).modal('show');
  var modal_id = 'kb-form-error-message-modal';

  $('#'+modal_id).remove();

  var kb_modal = $('<div id="'+modal_id+'" class="modal"></div>');
  var kb_modal_dialog = $('<div id="'+modal_id+'" class="modal-dialog"></div>');
  var kb_modal_content = $('<div class="modal-content"></div>');
  var kb_modal_header = $('<div class="modal-header"></div>');
  var kb_modal_body = $('<div class="modal-body"></div>');
  var kb_modal_footer = $('<div class="modal-footer"></div>');

  $(kb_modal_header).html('<h4 class="kb-bootstrap-bold-highlight">Please fix following errors to submit:</h4>');
  $(kb_modal_body).html(error_message);
  $(kb_modal_footer).append( $('<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>'));

  $(kb_modal_content).append(kb_modal_header);
  $(kb_modal_content).append(kb_modal_body);
  $(kb_modal_content).append(kb_modal_footer);
  $(kb_modal_dialog).append(kb_modal_content);
  $(kb_modal).append(kb_modal_dialog);

  $('body').prepend(kb_modal);
  $(kb_modal).modal('show');

  /*
  <div id="<?php echo $form_attributes['id']; ?>-form-errors-dialog" class="form-errors-dialog modal-dialog">
  <div class="modal-content">
    <div class="modal-header"></div>
    <div class="modal-body">
      <div class="text-wrapper"></div>
    </div>
    <div class="modal-footer"></div>
  </div>
</div>
  */



  return true;
}


function kb_hide_submit_buttons(){
  $("form.kb-form .submit-buttons-wrapper section.kb-buttons-section-wrapper").hide();
  $("form.kb-form .submit-buttons-wrapper div.submitting-anime").show();
}
function kb_show_submit_buttons(){
  $("form.kb-form .submit-buttons-wrapper div.button-wrapper button").removeClass('kb-button-clicked');
  $("form.kb-form .submit-buttons-wrapper section.kb-buttons-section-wrapper").show();
  $("form.kb-form .submit-buttons-wrapper div.submitting-anime").hide();
}


function kb_init_collapsible_fieldsets(){
  $('.kb-form fieldset.collapsible').each(function(){
    if($(this).hasClass('closed')){
      $(this).find('div.text-wrapper').hide();
      $('div#page').trigger('kb_height_changed');
    }
    $(this).children('legend').click(kb_toggle_collapsible_fieldset);
  });
}

function kb_toggle_collapsible_fieldset(){
  var fieldset = $(this).parent();
  if($(fieldset).hasClass('closed')){
    $(fieldset).addClass('open');
    $(fieldset).removeClass('closed');
    $(fieldset).find('div.text-wrapper').show();
  }else{
    $(fieldset).removeClass('open');
    $(fieldset).addClass('closed');
    $(fieldset).find('div.text-wrapper').hide();
  }
  $(this).parents('form.kb-form').trigger('kb_collapsible_toggled');
  $('div#page').trigger('kb_height_changed');
  recalculate_heights();
}

function kb_form_required(field_id, is_required){
  var wrapper = $('#'+field_id).parent().parent();
  var label   = $('#'+field_id).parent().parent().find('label');
  var span   = $(label).find('span');

  if(is_required){
    $('#'+field_id).prop('required', true);
    $('#'+field_id).addClass('required');
    $(span).addClass('required');
  }else{
    $('#'+field_id).prop('required', false);
    $('#'+field_id).removeClass('required');
    $(span).removeClass('required');
  }
}

function kb_form_new_fields_added(){
  kb_form_init_per_field_validation();
  kb_form_init_regex_fields();
}

function kb_form_init_regex_fields(){
  $('input.kb-user-input-regex').each(function(){
    if(!$(this).parents('div.clone-item.new-item.sub-item-wrapper').length){
      $(this).removeClass('kb-user-input-regex').addClass('kb-user-input-regex-inited');
      $(this).change(kb_form_check_regex_field);
      $(this).keyup(kb_form_check_regex_field);
    }
  });
}

function kb_form_check_regex_field(e){
  var allow_keypress = true;
  var current_value = $(this).val();
  var regex = new RegExp($(this).attr('data-kb-regex-pattern'));
  var new_string = '';
  var did_find = false;

  for (var i = 0, len = current_value.length; i < len; i++) {
    var c = new String(current_value[i]);
    if(c.match(regex)){
      new_string += c;
    }else{
      did_find = true;
    }
  }
  if(!did_find){
    new_string = current_value;
  }
  
  $(this).val(new_string);
  return true;
}