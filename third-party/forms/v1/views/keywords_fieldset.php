<fieldset class="container <?php echo $classes;?>" id="fieldset-<?php echo $id;?>" data-id="<?php echo $id;?>">
  <legend id="legend-<?php echo $id;?>" class="kb-keywords-legend"><?php echo $full_label; ?></legend>
	<?php if(!$readonly): ?>
	<?php echo $input_content; ?>
	<div class="multi-copy-holder item-wrapper">
		<a href="#" class="delete-item">X</a>
		<input name="<?php echo $id;?>[new]" type="text" class="hidden-keyword" value="" disabled="disabled" />
		<span class="keyword">_keyword_</span>
	</div>
	<?php endif; ?>
	<div class="item-list-area" id="<?php echo $id;?>-item-list-area">
		<?php if( isset($form_value['existing']) && !empty($form_value['existing'])): ?>
		<?php foreach($form_value['existing'] as $keyword_id=>$word): ?>
		<div class="item-wrapper">
			<?php if(!$readonly): ?>
			<a href="#" class="delete-item">X</a>
			<?php endif; ?>
			<input id="fieldset-<?php echo $id;?>-item-<?php echo $keyword_id;?>" name="<?php echo $id;?>[existing][<?php echo $keyword_id;?>]" type="text" class="hidden-keyword" value="<?php echo htmlentities($word);?>" />
			<span class="keyword"><?php echo htmlentities($word);?></span>
		</div>
		<?php endforeach; ?>
		<?php endif; ?>
		<?php if( isset($form_value['new']) && !empty($form_value['new'])): ?>
		<?php foreach($form_value['new'] as $keyword_id=>$word): ?>
		<div class="item-wrapper new">
			<a href="#" class="delete-item">X</a>
			<input id="fieldset-<?php echo $id;?>-item-<?php echo $keyword_id;?>" name="<?php echo $id;?>[new][<?php echo $keyword_id;?>]" type="text" class="hidden-keyword" value="<?php echo htmlentities($word);?>" />
			<span class="keyword"><?php echo htmlentities($word);?></span>
		</div>
		<?php endforeach; ?>
		<?php endif; ?>
	</div>
  <div class="clearfix"></div>
</fieldset>
