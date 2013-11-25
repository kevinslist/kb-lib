$(document).ready(init_bsg_camera_image_fields);


function init_bsg_camera_image_fields(){
  $('div.field-input.bsg-camera-image').each( function(){
    if(!$(this).find('button.bsg-camera-image-start-camera:visible').length){
      var button_id = $(this).find('div.bsg-camera-image-holder').attr('data-start-camera-button-id');
      $('#'+button_id).click({'modal':$(this).find('div.bsg-camera-modal')}, bsg_camera_show_modal);
    }
    $(this).find('div.bsg-camera-modal').on('shown.bs.modal', bsg_camera_image_init_video);
    $(this).find('div.bsg-camera-modal').on('hidden.bs.modal', bsg_camera_image_stop_video);
    $(this).find('button.bsg-snap-photo-button').click(bsg_camera_snap_photo);
    $(this).find('button.bsg-camera-cropped-image-button').click(bsg_camera_crop_image);
    $(this).find('button.bsg-camera-restart-video-button').click(bsg_camera_restart_video);
  });
  
}

function bsg_camera_show_modal(a, b){
  $(a.data.modal).modal('show');
  $(window).trigger('bsg_resize_trigger');
  return false;
}

function bsg_camera_crop_image(){
  var the_modal = $(this).parents('div.bsg-camera-modal');

  $(the_modal).find('button.bsg-camera-restart-video-button').hide();
  $(the_modal).find('button.bsg-camera-cropped-image-button').hide();
  $(the_modal).find('button.bsg-snap-photo-button').show();

  var video_obj = $(the_modal).find('.bsg-camera-media-area-wrapper video');
  var video = document.getElementById($(video_obj).attr('id'));

  var image_obj = $(the_modal).find('.bsg-camera-media-area-wrapper img.bsg-camera-image');
  var jcrop_api = $(image_obj).data('jcrop-object');
  var html_image = document.getElementById($(image_obj).attr('id'));

  var crop_square = $('div.jcrop-holder:visible div:first');
  var p = $(crop_square).position();

  var cropped_image_width = $(crop_square).width();
  var cropped_image_height = $(crop_square).height();
  var cropped_image_x = p.left;
  var cropped_image_y = p.top;
  var new_image_height = 200;
  var new_image_width = 200;
  
  localStorage.setItem("bsg_camera_image_saved_dimensions_top", p.top);
  localStorage.setItem("bsg_camera_image_saved_dimensions_left", p.left);
  localStorage.setItem("bsg_camera_image_saved_dimensions_width", cropped_image_width);
  localStorage.setItem("bsg_camera_image_saved_dimensions_height", cropped_image_height);

  var new_canvas = $('<canvas height="' + new_image_height + 'px" width="' + new_image_width + 'px">');
  $(new_canvas).attr('id', 'bsg-camera-temp-canvas');

  $(the_modal).parents('div.field-wrapper.bsg-camera-image').append($(new_canvas));
  
  var new_canvas_obj = document.getElementById('bsg-camera-temp-canvas');
  var new_context = new_canvas_obj.getContext("2d");
  new_context.drawImage(html_image, cropped_image_x, cropped_image_y, cropped_image_width, cropped_image_height, 0, 0, new_image_width, new_image_height);


  var place_image = $(the_modal).parents('div.field-wrapper.bsg-camera-image').find('img.bsg-camera-cropped-image-holder');
  $(place_image).attr('src', new_canvas_obj.toDataURL());
  $(new_canvas).remove();

  jcrop_api.destroy();
  $(image_obj).data('jcrop-object', false);

  $(image_obj).hide();
  var lvs = $(video).data('local_media_stream');
  lvs.stop();
  $(video_obj).show();
  $(the_modal).modal('hide');
  $(the_modal).parents('div.field-wrapper.bsg-camera-image').trigger('bsg_camera_image_selected');
}

function bsg_camera_snap_photo(){
  var parent = $(this).parents('.modal-dialog');
  var video_obj = $(parent).find('.bsg-camera-media-area-wrapper video.bsg-camera-video');
  var video = document.getElementById($(video_obj).attr('id'));
  var canvas_obj = $(parent).find('.bsg-camera-media-area-wrapper canvas.bsg-camera-canvas');
  var canvas = document.getElementById($(canvas_obj).attr('id'));
  var context = canvas.getContext("2d");
  var image_obj = $(parent).find('.bsg-camera-media-area-wrapper img.bsg-camera-image');


  context.drawImage(video, 0, 0, 640, 480);
  $(image_obj).attr('src', canvas.toDataURL());
 
  $(image_obj).show();
  $(video_obj).hide();


  $(parent).find('button.bsg-camera-restart-video-button').show();
  $(parent).find('button.bsg-camera-cropped-image-button').show();
  $(parent).find('button.bsg-snap-photo-button').hide();
  var jcrop_api = null;

  var bsg_camera_image_saved_dimensions_top = localStorage.getItem("bsg_camera_image_saved_dimensions_top");
  var bsg_camera_image_saved_dimensions_left = localStorage.getItem("bsg_camera_image_saved_dimensions_left");
  var bsg_camera_image_saved_dimensions_width = localStorage.getItem("bsg_camera_image_saved_dimensions_width");
  var bsg_camera_image_saved_dimensions_height = localStorage.getItem("bsg_camera_image_saved_dimensions_height");

  var p_left = 190;
  var p_top = 110;
  var p_right = 450;
  var p_bottom = 370;

  if(bsg_camera_image_saved_dimensions_top && bsg_camera_image_saved_dimensions_left && bsg_camera_image_saved_dimensions_width && bsg_camera_image_saved_dimensions_height){
    p_left = new Number(bsg_camera_image_saved_dimensions_left);
    p_top = new Number(bsg_camera_image_saved_dimensions_top);
    p_right = p_left + new Number(bsg_camera_image_saved_dimensions_width);
    p_bottom = p_top + new Number(bsg_camera_image_saved_dimensions_height);
  }


  $(image_obj).Jcrop({
    aspectRatio: 1,
    onSelect: bsg_camera_image_check_crop_selection,
    onChange: bsg_camera_image_check_crop_selection,
    onRelease: bsg_camera_image_check_crop_selection,
    setSelect:   [ p_left, p_top, p_right, p_bottom ]
  }, function(){
    $(image_obj).data('jcrop-object', this);
  });
  return false;
}

function bsg_camera_image_check_crop_selection(c){
  if(undefined == c){
    $('button.bsg-camera-cropped-image-button').prop('disabled', true);
    bsg_camera_set_select_previous();
  }else{
    $('button.bsg-camera-cropped-image-button').prop('disabled', false);
  }
}

function bsg_camera_set_select_previous(){
  var image_obj = $('.bsg-camera-media-area-wrapper img.bsg-camera-image');
  var jcrop_api = $(image_obj).data('jcrop-object');

  var bsg_camera_image_saved_dimensions_top = localStorage.getItem("bsg_camera_image_saved_dimensions_top");
  var bsg_camera_image_saved_dimensions_left = localStorage.getItem("bsg_camera_image_saved_dimensions_left");
  var bsg_camera_image_saved_dimensions_width = localStorage.getItem("bsg_camera_image_saved_dimensions_width");
  var bsg_camera_image_saved_dimensions_height = localStorage.getItem("bsg_camera_image_saved_dimensions_height");
  var p_left = 190;
  var p_top = 110;
  var p_right = 450;
  var p_bottom = 370;

  if(bsg_camera_image_saved_dimensions_top && bsg_camera_image_saved_dimensions_left && bsg_camera_image_saved_dimensions_width && bsg_camera_image_saved_dimensions_height){
    p_left = new Number(bsg_camera_image_saved_dimensions_left);
    p_top = new Number(bsg_camera_image_saved_dimensions_top);
    p_right = p_left + new Number(bsg_camera_image_saved_dimensions_width);
    p_bottom = p_top + new Number(bsg_camera_image_saved_dimensions_height);
  }
  jcrop_api.setSelect([ p_left, p_top, p_right, p_bottom ]);
}

function bsg_camera_restart_video(){
  var parent = $(this).parents('.modal-dialog');
  $(parent).find('button.bsg-camera-restart-video-button').hide();
  $(parent).find('button.bsg-camera-cropped-image-button').hide();
  $(parent).find('button.bsg-snap-photo-button').show();
  var video_obj = $(parent).find('.bsg-camera-media-area-wrapper video');
  var canvas_obj = $(parent).find('.bsg-camera-media-area-wrapper canvas');
  var image_obj = $(parent).find('.bsg-camera-media-area-wrapper img.bsg-camera-image');
  $(video_obj).show();
  var jcrop_api = $(image_obj).data('jcrop-object');
  jcrop_api.destroy();
  $(image_obj).data('jcrop-object', false);
  $(image_obj).hide();
  $(window).trigger('bsg_resize_trigger');
}

function bsg_camera_image_stop_video(){
  $(this).find('button.bsg-camera-restart-video-button').hide();
  $(this).find('button.bsg-camera-cropped-image-button').hide();
  $(this).find('button.bsg-snap-photo-button').show();
  var video = $($(this).attr('data-bsg-video-id'));
  $(video).show();
  var canvas = $($(this).attr('data-bsg-canvas-id'));
  var image_obj = $(this).find('.bsg-camera-media-area-wrapper img.bsg-camera-image');

  var jcrop_api = $(image_obj).data('jcrop-object');
  if(jcrop_api){
    jcrop_api.destroy();
  }
  var lvs = $(video).data('local_media_stream');
  lvs.stop();
  $(image_obj).hide();
  return;
}

function bsg_camera_image_init_video(){
  var video = $($(this).attr('data-bsg-video-id'));
  var video_obj = {
    "video":true
  };
  var video_src = '';
  var localMediaStream = null;

  navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
  navigator.getUserMedia(video_obj, function(stream){
    video_src = (navigator.webkitGetUserMedia) ? window.webkitURL.createObjectURL(stream) : ((navigator.mozGetUserMedia) ? window.URL.createObjectURL(stream) : stream);
     
    $(video).attr('src', video_src);
    $(video).data('local_media_stream', stream);
    $(window).trigger('bsg_resize_trigger');
  }, bsg_camera_image_on_camera_fail);

  $(window).trigger('bsg_resize_trigger');
}


function bsg_camera_image_on_camera_fail(e){
  log('Camera did not work.', e);
}