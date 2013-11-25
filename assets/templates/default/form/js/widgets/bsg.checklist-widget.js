var bsg_widget_checklist_ajax_loader_inited_classname = 'bsg-widget-checklist-ajax-loader-inited';
var bsg_widget_checklist_previous_value_string = 'bsg-widget-checklist-previous-value';
$(document).ready(init_bsg_checklist_widget);

function init_bsg_checklist_widget(){
  init_bsg_checklist_widget_ajax_loader();
  init_bsg_checklist_dynamic_elements();
  $('form.bsg-form').bind('bsg_checklist_loaded', bsg_checklist_new_checklist_loaded);
}

function bsg_checklist_new_checklist_loaded(){
  $('fieldset.buttons-wrapper').hide();
  $('div.bsg-checklist-widget-load-container').hide();
  init_bsg_checklist_dynamic_elements();
  $('div.bsg-checklist-widget-load-container').show();
  $('fieldset.buttons-wrapper').show();
  recalculate_heights();
}

function init_bsg_checklist_dynamic_elements(){
  $('input.bsg-radio.readonly').click(function(){return false;});
  $('a.bsg-checklist-show-comment-link').each(function(){
    if(!$(this).hasClass('init_bsg_checklist_dynamic_elements')){
      $(this).addClass('init_bsg_checklist_dynamic_elements');
      $(this).click(bsg_checklist_show_comment_area);
    }
  });
  
  
  $('a.bsg-checklist-clear-comment-link').each(function(){
    if(!$(this).hasClass('init_bsg_checklist_dynamic_elements')){
      $(this).addClass('init_bsg_checklist_dynamic_elements');
      $(this).click(bsg_checklist_clear_comment_area);
    }
  });
}
function bsg_checklist_show_comment_area(){
  var question_wrapper = $(this).parents('div.bsg-checklist-question-wrapper:first');
  $(question_wrapper).removeClass('no-comment').addClass('has-comment');
  $('div#page').trigger('bsg_height_changed');
  return false;
}
function bsg_checklist_clear_comment_area(){
  var question_wrapper = $(this).parents('div.bsg-checklist-question-wrapper:first');
  $(question_wrapper).removeClass('has-comment').addClass('no-comment');
  $(question_wrapper).find('textarea.comment').val('');
  return false;
}

function init_bsg_checklist_widget_ajax_loader(){
  $('div.bsg-checklist-widget-load-container').each(function(){
    if( !$(this).hasClass(bsg_widget_checklist_ajax_loader_inited_classname)){
      var load_trigger = $.trim($(this).attr('data-load-trigger'));
      if('' != load_trigger){
        $('#'+load_trigger).attr(bsg_widget_checklist_previous_value_string, $('#'+load_trigger).val());
        $('#'+load_trigger).on("change", {ajax_loader:$(this), field_id:$(this).attr('data-field-id'), load_trigger:$(this).attr('data-load-trigger')}, bsg_widget_checklist_load_trigger_changed);
        $(this).addClass(bsg_widget_checklist_ajax_loader_inited_classname);
      }
    }
  });
}

function bsg_widget_checklist_load_trigger_changed(event){
  var load_trigger_value = $.trim($(this).val());
  var confirmed = bsg_widget_confirm_load($(event.data.ajax_loader));
  if(confirmed){
    $(this).attr(bsg_widget_checklist_previous_value_string, $(this).val());
    if('' != load_trigger_value){
      var url = ajax_home + 'bsg_checklist_load/' + load_trigger_value + '/' + event.data.field_id;
      $(event.data.ajax_loader).load(url, bsg_checklist_load_callback_trigger);
    }else{
      $(event.data.ajax_loader).html('<div class="clearfix"></div>');
    }
  }else{
    $(this).val($(this).attr(bsg_widget_checklist_previous_value_string));
  }
}

function bsg_widget_confirm_load(load_area){
  var confirmed = true;
  var selected_count = $(load_area).find('input:checked').length;
  if(selected_count){
    confirmed = confirm('By changing checklists you will lose your current data, do you want to continue?');
  }
  return confirmed;
}

function bsg_checklist_load_callback_trigger(e){
  var bsg_checklist = $('#' + $(e).attr('id'));
  $('form.bsg-form').trigger('bsg_checklist_loaded', {checklist:bsg_checklist});
  $('div#page').trigger('bsg_height_changed');
}