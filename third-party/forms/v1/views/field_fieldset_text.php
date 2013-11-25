<div class="kb-container <?php echo $classes;?>" id="<?php echo $id;?>">
  <?php if(!empty($full_label)): ?>
  <div id="legend-<?php echo $id;?>"><div class="arrow"></div><div class="the-legend"><?php echo $full_label; ?></div></div>
  <?php endif; ?>
  <div class="text-wrapper"><?php echo $form_value; ?></div>
  <div class="clearfix"></div>
</div>
