<div id="kb-col-wrapper<?=$id;?>" class="kb-col-wrapper <?php echo $classes;?>" <?php foreach($attributes as $attr_key=>$attr_value){ echo ' ' . $attr_key . '="' . $attr_value . '" '; }?>>
  <div class="kb-col-inner">
  <?php if(!empty($legend)): ?>
  <div id="kb-col-legend-<?=$id;?>" class="kb-col-legend row">
    <div class="col-12">
    <?= $legend; ?>
    </div>
  </div>
  <?php endif; ?>
  <div id="kb-col-contents-<?=$id;?>" class="kb-col-contents">
    <?php echo $content; ?>
  </div>
  <div class="clearfix"></div>
  </div>
</div>
