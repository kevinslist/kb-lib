<div class="field-wrapper <?php
  if(!empty($classes)){
   if(is_array($classes)){
     echo implode(' ', $classes);
   }else{
     echo $classes;
   }
  }
?>" id="field-wrapper-<?php echo $id;?>" data-field-id="<?php echo $id;?>">
  <?php echo $content; ?>
  <?php if(isset($error) && $error): ?>
  <div class="field-error" id="field-error-<?php echo $id;?>">
    <?php echo $error; ?>
  </div>
  <?php endif; ?>
  <div class="clearfix"></div>
</div>
