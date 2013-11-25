<div class="kb-container <?php echo $classes;?>" <?php foreach($attributes as $attr_key=>$attr_value){
       echo ' ' . $attr_key . '="' . $attr_value . '" ';
     }?> id="kb-container-<?php echo $id;?>">
  <?php if(!empty($legend)): ?>
  <div class="legend-wrapper">
    <?php echo $legend; ?>
    <div class="clearfix"></div>
  </div>
  <?php endif; ?>
  <div class="content-wrapper">
    <?php echo $content; ?>
    <div class="clearfix"></div>
  </div>
  <div class="clearfix"></div>
</div>
