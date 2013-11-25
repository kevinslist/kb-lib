<div id="kb-checklist-widget-wrapper-<?php echo $id; ?>" class="kb-checklist-widget-load-container ajax-load-container <?php echo !empty($classes) ? (is_array($classes) ? implode(' ', $classes) : $classes): '';?>" data-field-id="<?php echo $id; ?>"  <?php if($load_trigger): ?>data-load-trigger="<?php echo $load_trigger?>"<?php endif; ?>>
<?php echo $content; ?>
</div>
