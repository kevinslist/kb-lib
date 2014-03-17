$(document).ready(init_select_fields);


function init_select_fields(){
	$('.bsg-form select').each( function(){
    if($(this).attr('required')){
      $(this).find('option:first').addClass('disabled');
    }
  });
}