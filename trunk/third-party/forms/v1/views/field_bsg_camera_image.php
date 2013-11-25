<div class="field-input <?php echo $classes;?>" id="input-<?php echo $id;?>">
  <button type="button"  data-toggle="modal" data-target="#<?=$id;?>-modal-wrapper" id="<?php echo $id;?>-kb-camera-image-start-camera" class="kb-button kb-camera-image-start-camera"><span class="glyphicon glyphicon-camera"></span>Start Camera</button>
  <input class="field-input <?php echo $classes .  BSG_USER_INPUT_CLASS;?>" type="hidden" id="<?php echo $id;?>" name="<?php echo $id;?>" value="<?=$form_value;?>" <?php echo $attributes; ?> />

  <div class="kb-camera-image-holder <?php echo $classes .  BSG_USER_INPUT_CLASS;?>" id="<?php echo $id;?>-kb-camera-holder"  <?php echo $attributes; ?>>
    <img id="<?php echo $id;?>-kb-camera-cropped-image-holder" class="kb-camera-cropped-image-holder" src="<?=$image_src?>" />
  </div>

  <div class="modal kb-camera-modal" id="<?=$id;?>-modal-wrapper" data-kb-video-id="#<?=$id;?>-video" data-kb-canvas-id="#<?=$id;?>-canvas">
    <div class="modal-dialog">
          <div class="kb-camera-button-wrapper">
            <button href="#" class="kb-camera-cropped-image-button" type="button">Use Cropped Image</button>
            <button href="#" class="kb-camera-restart-video-button" type="button">Restart Video</button>
            <button href="#" class="kb-snap-photo-button" type="button">Snap Photo</button>
            <button href="#" class="kb-close-modal-button" type="button" data-dismiss="modal">Cancel</button>
          </div>
          <div class="kb-camera-media-area-wrapper">
            <video id="<?=$id;?>-video" width="640" height="480" autoplay class="kb-camera-video"></video>
            <canvas id="<?=$id;?>-canvas" width="640" height="480" class="kb-camera-canvas"></canvas>
            <image id="<?=$id;?>-img" width="640" height="480"  class="kb-camera-image" />
          </div>
    </div>
  </div>
</div>
