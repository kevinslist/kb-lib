<div class="kb-one-to-many kb-one-to-many-container <?php echo is_array($classes) ? implode(' ', $classes) : $classes; ?>" id="fieldset-<?php echo $id;?>" data-model="<?php echo $model; ?>" data-new-item-on-init="<?php echo $new_item_on_init; ?>">
  <div id="legend-<?php echo $id;?>" class="kb-one-to-many-legend legend-wrapper">
		<?php echo $legend; ?>
	</div>
  <?php if(!$readonly): ?>
  <div class="clone-item new-item sub-item-wrapper">
    <?php echo $clone_content; ?>
    <div class="clearfix"></div>
    <?php if($can_delete_item): ?>
    <a href="#" title="Click to remove item" class="delete-item <?php echo $delete_warn; ?>"><span class="glyphicon glyphicon-remove"></span></a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <div class="content-area">
    <?php
      if(isset($existing_items) && !empty($existing_items)):
        foreach($existing_items as $existing_item): ?>
    <div class="existing-item sub-item-wrapper">
      <?php if($can_delete_item): ?>
      <?php endif; ?>
      <?php echo $existing_item; ?>
      <div class="clearfix"></div>
      <a href="#" title="Click to remove item" class="delete-item <?php echo $delete_warn; ?>"><span class="glyphicon glyphicon-remove"></span></a>
    </div>
    <?php
        endforeach;
      endif;
    ?>
    <?php
      if(isset($new_items) && !empty($new_items)):
        foreach($new_items as $new_item): ?>
    <div class="new-item sub-item-wrapper">
      <?php echo $new_item; ?>
      <div class="clearfix"></div>
      <?php if($can_delete_item): ?>
      <a href="#" title="Click to remove item" class="delete-item  <?php echo $delete_warn; ?>"><span class="glyphicon glyphicon-remove"></span></a>
      <?php endif; ?>
    </div>
    <?php
        endforeach;
      endif;
    ?>
  </div>
  <?php if(!$readonly): ?>
    <a class="kb-one-to-many-link btn" href="#"><i class="glyphicon glyphicon-plus-sign"></i>&nbsp; <?=$add_more_label?></a>
  <?php endif; ?>
  <div class="clearfix"></div>
</div>
