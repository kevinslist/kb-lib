<label id="label-<?php echo $id;?>" for="<?php echo $id;?>"  <?php echo $attributes; ?> class="checkbox-inline kb-checkbox-label">
  <span class="field-input <?php echo $classes;?>" id="input-<?php echo $id;?>">
    <input type="<?php echo $widget;?>" id="<?php echo $id;?>" class="<?php echo $classes . BSG_USER_INPUT_CLASS;?>" name="<?php echo $id;?>" value="1" <?php echo $attributes; ?> />
  </span>
  <span class="label-text"><?php echo $full_label; ?></span>
  <span class="label-icon <?php echo $classes; ?> glyphicon glyphicon-asterisk" title="Field Required"></span>
  <div class="clearfix" style="display:none"></div>
</label>


