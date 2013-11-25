<?php if(!empty($category) && $old_category != $category): ?>
<h2><?php echo $category; ?></h2>
<?php endif; ?>
<div class="checklist-question-container <?php echo $classes;?>" id="checklist-question-<?php echo $id;?>" data-id="<?php echo $id;?>">
  <div class="checklist-answer-container">
    <?php foreach($answers as $text=>$answer_value): ?>
      <input type="radio" name="<?php echo $id;?>" id="<?php echo $id;?>" class="<?php echo $classes;?>" />
      <?php echo $text; ?>
    <?php endforeach ?>
    <div class="clearfix"></div>
  </div>
  <div class="checklist-text">
    <?php echo $question; ?>
    <div class="clearfix"></div>
  </div>
  <div class="clearfix"></div>
</div>
