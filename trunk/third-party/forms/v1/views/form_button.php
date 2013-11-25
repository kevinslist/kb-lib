<div class="button-wrapper <?php echo $form_id;?> <?php echo $value;?>" id="button-wrapper-<?php echo $index;?>">
  <button type="<?php echo $type;?>" id="<?php echo $form_id . '-' . $type . '-'. $name . '-' . $index;?>" name="<?php echo $name;?>" class="btn <?php echo $form_id;?> <?php echo $value;?>" value="<?php echo $value;?>" data-value="<?php echo $value;?>" data-confirm-message="<?=$confirm?>">
  <?php echo $label; ?>
  </button>
</div>
