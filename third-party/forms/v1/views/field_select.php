<?php bsg_form_widget_select::$did_select = false; ?>
<div class="field-input <?php echo $classes;?>" id="input-<?php echo $id;?>">
  <select id="<?php echo $id;?>" name="<?php echo $id;?>" class="<?php echo $classes . BSG_USER_INPUT_CLASS;?>" <?php echo $attributes; ?>>
  <?php if($initial): ?>
    <option value=""><?php echo $initial; ?></option>
  <?php endif; ?>
  <?php foreach($options as $option_value=>$option_label): ?>
  <?php echo bsg_form_widget_select::render_option($option_value, $option_label, $form_value); ?>
  <?php endforeach; ?>
  </select>
</div>