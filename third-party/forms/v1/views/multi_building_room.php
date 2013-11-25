<?php if(!$readonly): ?>
<div class="multi-copy-holder item-wrapper">
	<a href="#" class="delete-item">X</a>

	<div class="building-wrapper">
		<label>Building</label>
		<div class="input-wrapper-building-<?php echo $id;?> input-wrapper">

			<select name="<?php echo $id;?>" data-type="building" size="1">
			<?php if($initial): ?>
				<option value=""><?php echo $initial; ?></option>
			<?php endif; ?>
			<?php foreach($buildings as $option_value => $option_label): ?>
			<?php echo bsg_form_widget_select::render_option($option_value, $option_label, null); ?>
			<?php endforeach; ?>
			</select>

		</div>
	</div>
	<div class="room-wrapper">
		<label>Room</label>
		<div class="input-wrapper-room-<?php echo $id;?> input-wrapper">

			<select name="<?php echo $id;?>" data-type="room" size="1">
			<?php if($initial): ?>
				<option value=""><?php echo $initial; ?></option>
			<?php endif; ?>
			</select>

		</div>
	</div>
	<div class="clearfix"></div>
</div>
<?php endif; ?>
<div class="item-list-area" id="<?php echo $id;?>-item-list-area">

	<?php if(isset($form_value['existing']) && is_array($form_value['existing'])): ?>
	<?php foreach($form_value['existing'] as $item_id => $building_room): ?>
	<?php if('' != $building_room['building']): ?>
	<div class="item-wrapper" id="fieldset-<?php echo $id; ?>-item-<?php echo $item_id; ?>">
		<?php if(!$readonly): ?><a href="#" class="delete-item">X</a><?php endif; ?>
		<div class="building-wrapper">
			<label>Building</label>
			<div class="input-wrapper-building-<?php echo $id;?> input-wrapper">
				<?php if($readonly): ?>
				<span class="buildingroom-readonly"><?php echo empty($building_room['building']) ? '' : $building_room['building']; ?></span>
				<?php else: ?>
				<select name="<?php echo $id;?>[existing][<?php echo $item_id; ?>][building]" data-type="building" size="1">
				<?php if($initial): ?>
					<option value=""><?php echo $initial; ?></option>
				<?php endif; ?>
				<?php bsg_form_widget_select::$did_select = false; ?>
				<?php foreach($buildings as $option_value => $option_label): ?>
				<?php echo bsg_form_widget_select::render_option($option_value, $option_label, $building_room['building']); ?>
				<?php endforeach; ?>
				</select>
				<?php endif; ?>
			</div>
		</div>
		<div class="room-wrapper">
			<label>Room</label>
			<div class="input-wrapper-room-<?php echo $id;?> input-wrapper">

				<?php if($readonly): ?>
				<span class="buildingroom-readonly"><?php echo empty($building_room['room']) ? '' : $building_room['room']; ?></span>
				<?php else: ?>
				<?php
					$rooms = kb::get_rooms($building_room['building']);
				?>
				<select name="<?php echo $id;?>[existing][<?php echo $item_id; ?>][room]" data-type="room" size="1">
				<?php if($initial): ?>
					<option value=""><?php echo $initial; ?></option>
				<?php endif; ?>
				<?php bsg_form_widget_select::$did_select = false; ?>
				<?php foreach($rooms as $option_value => $option_label): ?>
				<?php echo bsg_form_widget_select::render_option($option_value, $option_label, $building_room['room']); ?>
				<?php endforeach; ?>
				</select>
				<?php endif; ?>
			</div>
		</div>
		<div class="clearfix"></div>
	</div>
	<?php endif; ?>
	<?php endforeach; ?>
	<?php endif; ?>



	<?php if(isset($form_value['new']) && is_array($form_value['new'])): ?>
	<?php $new_item_count = -1; ?>
	<?php foreach($form_value['new'] as $item_id => $building_room): ?>
	<?php if('' != $building_room['building']): ?>
	<?php $new_item_count++; ?>
	<div class="item-wrapper" id="fieldset-<?php echo $id; ?>-item-<?php echo $new_item_count; ?>">
		<a href="#" class="delete-item">X</a>
		<div class="building-wrapper">
			<label>Building</label>
			<div class="input-wrapper-building-<?php echo $id;?> input-wrapper">
				<select name="<?php echo $id;?>[new][<?php echo $new_item_count; ?>][building]" data-type="building" size="1">
				<?php if($initial): ?>
					<option value=""><?php echo $initial; ?></option>
				<?php endif; ?>
				<?php bsg_form_widget_select::$did_select = false; ?>
				<?php foreach($buildings as $option_value => $option_label): ?>
				<?php echo bsg_form_widget_select::render_option($option_value, $option_label, $building_room['building']); ?>
				<?php endforeach; ?>
				</select>

			</div>
		</div>
		<div class="room-wrapper">
			<label>Room</label>
			<div class="input-wrapper-room-<?php echo $id;?> input-wrapper">
				<?php
					$rooms = kb::get_rooms($building_room['building']);
				?>
				<select name="<?php echo $id;?>[new][<?php echo $new_item_count; ?>][room]" data-type="room" size="1">
				<?php if($initial): ?>
					<option value=""><?php echo $initial; ?></option>
				<?php endif; ?>
				<?php bsg_form_widget_select::$did_select = false; ?>
				<?php foreach($rooms as $option_value => $option_label): ?>
				<?php echo bsg_form_widget_select::render_option($option_value, $option_label, $building_room['room']); ?>
				<?php endforeach; ?>
				</select>

			</div>
		</div>
		<div class="clearfix"></div>
	</div>
	<?php endif; ?>
	<?php endforeach; ?>
	<?php endif; ?>
</div>
