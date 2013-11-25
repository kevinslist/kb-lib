<fieldset class="kb-add-more container <?php echo is_array($classes) ? implode(' ', $classes) : $classes; ?>" id="fieldset-<?php echo $id;?>" data-room-lookup="<?php echo $room_lookup; ?>">
	<legend id="legend-<?php echo $id;?>" class="kb-add-more-legend">
		<?php echo $full_label; ?><?php if(!$readonly): ?> <a class="add-more-link" href="#">+ Add More</a><?php endif; ?>
	</legend>
  <?php echo $content; ?>
  <div class="clearfix"></div>
</fieldset>
