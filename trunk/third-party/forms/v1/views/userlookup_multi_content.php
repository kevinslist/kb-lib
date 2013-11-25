<?php echo $input_content; ?>
<div class="multi-copy-holder item-wrapper">
	<a href="#" class="delete-item">X</a>
	<input name="<?php echo $id;?>[new]" type="text" disabled="disabled" class="hidden-uupic" value="" />
	<span class="displayname"></span>
</div>
<div class="item-list-area" id="<?php echo $id;?>-item-list-area">
<?php if(isset($form_value['existing']) && is_array($form_value['existing'])): ?>
<?php foreach($form_value['existing'] as $uupic): ?>
<?php $displayname = VwNedExtract::displayname($uupic); ?>
<div class="item-wrapper" data-uupic="<?php echo $uupic; ?>">
	<a href="#" class="delete-item">X</a>
	<input name="<?php echo $id;?>[existing][<?php echo $uupic; ?>]" type="text" class="hidden-uupic" value="<?php echo $uupic; ?>" />
	<span class="displayname"><?php echo $displayname; ?></span>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php if(isset($form_value['new']) && is_array($form_value['new'])): ?>
<?php foreach($form_value['new'] as $uupic): ?>
<?php $displayname = VwNedExtract::displayname($uupic); ?>
<div class="item-wrapper" data-uupic="<?php echo $uupic; ?>">
	<a href="#" class="delete-item">X</a>
	<input name="<?php echo $id;?>[new][<?php echo $uupic; ?>]" type="text" class="hidden-uupic" value="<?php echo $uupic; ?>" />
	<span class="displayname"><?php echo $displayname; ?></span>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
