<div id="kb-row-<?=$id;?>" class="kb-row <?php echo $classes;?>" <?php foreach($attributes as $attr_key=>$attr_value){ echo ' ' . $attr_key . '="' . $attr_value . '" '; }?>>
  <div class="kb-row-inner">
    <?php if(!empty($legend)): ?>
    <div id="kb-row-legend-<?=$id;?>" class="kb-row-legend row">
      <div class="col-12">
      <?= $legend; ?>
      </div>
    </div>
    <?php endif; ?>
    <div id="kb-row-contents-<?=$id;?>" class="kb-row-contents row <?php echo $classes;?>"  <?php foreach($attributes as $attr_key=>$attr_value){ echo ' ' . $attr_key . '="' . $attr_value . '" '; }?>>
      <?php echo $content; ?>
    </div>
    <div class="clearfix"></div>
  </div>
</div>
