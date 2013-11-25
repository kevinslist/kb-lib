$(document).ready(init_bsg_one_to_many);
var did_init_bsg_one_to_many = false;
var bsg_one_to_many_animating_class = 'bsg-one-to-many-animating-in-progress';
function init_bsg_one_to_many() {
  $('form.bsg-form .bsg-one-to-many-container a.bsg-one-to-many-link').click(bsg_one_to_many_duplicate);
  $('form.bsg-form').bind('bsg_form_pre_submit', bsg_one_to_many_update_remove_clones);
  var content_area  = $(this).find('div.content-area');
  $(content_area).find('div.sub-item-wrapper:visible a.delete-item').click(bsg_one_to_many_delete_item);

  $('form.bsg-form .bsg-one-to-many-container').each(function(){
    var new_item_on_init = $(this).attr('data-new-item-on-init');
    if (new_item_on_init) {
      $(this).find('a.bsg-one-to-many-link').click();
    }
  });
  $(content_area).bind('bsg_one_to_many_update_zebra_classes', bsg_one_to_many_update_zebra_classes);
  $(content_area).trigger('bsg_one_to_many_update_zebra_classes');
  did_init_bsg_one_to_many = true;
}


function bsg_one_to_many_duplicate(){
  var fieldset      = $(this).parent().parent();
  var clone_item    = $(fieldset).find('div.clone-item');
  var content_area  = $(fieldset).find('div.content-area');
  var new_count     = $(content_area).find('div.new-item').length + 1;
  var the_form      = $(this).parents('form.bsg-form:first');
  
  var clone           = $(clone_item).clone(true, true);
  var html_string     = $(clone).html();
  var new_html_string = html_string.replace(/_cl0ne_/g, new_count);

  $(clone).html(new_html_string);
  $(clone).removeClass('clone-item');
  $(clone).find('a.delete-item').click(bsg_one_to_many_delete_item);

  $(clone).find('.bsg-user-input.bsg-to-be-required').each(function(){
    //var original_id = $(this).attr('id');
    //var escaped_id = original_id.replace(/([\[\]])/g, '\\\$1');
    //bsg_form_required(escaped_id, true);

    var wrapper = $(this).parent().parent();
    var label   = $(this).parent().parent().find('label');
    var span   = $(label).find('span');

    $(this).prop('required', true);
    $(this).addClass('required');
    $(span).addClass('required');
    $(wrapper).addClass('required');


  });

  if(did_init_bsg_one_to_many){
    $(clone).hide();
    $(content_area).append(clone);
    $(content_area).addClass(bsg_one_to_many_animating_class);
    $(clone).slideDown(222, bsg_one_to_many_duplicate_complete);
  }else{
    $(content_area).append(clone);
    $(content_area).trigger('bsg_one_to_many_cloned');
    $(content_area).trigger('bsg_one_to_many_update_zebra_classes');
    $(fieldset).trigger('bsg_one_to_many_cloned_animation_complete');
  }
/*
  if($('input.bsg-date').length){
    try{
      init_bsg_date_fields();
    }catch(exc){
      log('bsg-date.js not defined?');
    }
  }
*/
  $(content_area).trigger('bsg_one_to_many_cloned');
  $(content_area).trigger('bsg_one_to_many_update_zebra_classes');
  return false;
}
function bsg_one_to_many_duplicate_complete(p, a){
  $('.'+bsg_one_to_many_animating_class).trigger('bsg_one_to_many_cloned');
  $('.'+bsg_one_to_many_animating_class).trigger('bsg_one_to_many_update_zebra_classes');
  $('.'+bsg_one_to_many_animating_class).trigger('bsg_one_to_many_cloned_animation_complete');
  $(window).trigger('bsg_resize_trigger');
}


function bsg_one_to_many_update_zebra_classes(){
  $(this).find('div.sub-item-wrapper').each(function(){
    $(this).removeClass('odd');
    $(this).removeClass('first');
    $(this).removeClass('last');
  });
  $(this).find('div.sub-item-wrapper:visible:odd').addClass('odd');
  $(this).find('div.sub-item-wrapper:visible:first').addClass('first');
  $(this).find('div.sub-item-wrapper:visible:last').addClass('last');
}

function bsg_one_to_many_update_remove_clones(){
  $(this).find('.bsg-one-to-many-container div.clone-item').remove();
}

function bsg_one_to_many_delete_item(){
  log('bsg_one_to_many_delete_item');
  var content_area = $(this).parent().parent();
  if (!$(this).hasClass('delete_warn') || confirm("Are you sure you want to remove item?")) {
    $(this).parent().remove();
    $(window).trigger('bsg_resize_trigger');
    $(content_area).trigger('bsg_one_to_many_update_zebra_classes');
  }
  return false;
}