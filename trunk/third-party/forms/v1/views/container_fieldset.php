<div id="field-wrapper-<?php echo $id;?>" <?php foreach($attributes as $attr_key=>$attr_value){
       echo ' ' . $attr_key . '="' . $attr_value . '" ';
     }?> class="field-wrapper fieldset <?php echo !empty($classes) ? (is_array($classes) ? implode(' ', $classes) : $classes): '';?>">
<fieldset class="container <?php echo $classes;?>" id="<?php echo $id;?>">
  <?php if(!empty($legend)): ?>
  <legend id="legend-<?php echo $id;?>"><div class="arrow"></div><div class="the-legend"><?php echo $legend; ?></div></legend>
  <?php endif; ?>
  <div class="text-wrapper">
    <?php if(isset($sub_legend) && !empty($sub_legend)): ?>
      <div class="sub-legend">
        <?php echo $sub_legend; ?>
        <div class="clearfix"></div>
      </div>
    <?php endif; ?>
    <?php echo $content; ?>
  </div>
  <div class="clearfix"></div>
</fieldset>
</div>
