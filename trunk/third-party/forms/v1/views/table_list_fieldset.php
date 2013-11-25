<div class="kb-table-list container kb-table-list-container <?php echo is_array($classes) ? implode(' ', $classes) : $classes; ?>" id="fieldset-<?php echo $id; ?>" data-model="<?php echo $model; ?>">
  <?php if (!empty($legend)): ?>
    <legend id="legend-<?php echo $id; ?>" class="kb-table-list-legend">
      <?php echo $legend; ?>
    </legend>
  <?php endif; ?>
  <div class="content-area">
    <?php
    echo!empty($existing) ? $existing : '';
    ?>
  </div>
  <div class="clearfix"></div>
</div>
