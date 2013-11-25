<?php if (!$multipart): ?>
  <?php echo form_open($action, $form_attributes); ?>
<?php else: ?>
  <?php echo form_open_multipart($action, $form_attributes); ?>
<?php endif; ?>
<div id="<?php echo $form_attributes['id']; ?>-form-legend-and-fields-and-buttons-wrapper">
  <?php echo $form_fields_legend; ?>
  <div id="<?php echo $form_attributes['id']; ?>-form-fields-and-buttons-wrapper">
    <?php if ($buttons_first): ?>
      <?php echo $buttons; ?>
    <?php endif; ?>
    <?php echo $form_fields_fieldset; ?>
    <?php if (!$buttons_first): ?>
      <?php echo $buttons; ?>
    <?php endif; ?>
    <div class="clearfix"></div>
  </div>
  <div class="clearfix"></div>
</div>

</form>

