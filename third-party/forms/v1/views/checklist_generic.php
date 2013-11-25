<div class="kb-checklist" id="kb-checklist-area-<?php echo $id;?>">
  <div class="kb-checklist-title-area-wrapper">
    <h2 class="checklist-title"><?php echo $checklist->checklist_title; ?></h2>
    <div class="clearfix"></div>
  </div>
  <div class="kb-checklist-description-area-wrapper">
    <?php echo $checklist->checklist_description; ?>
    <div class="clearfix"></div>
  </div>
  <div class="kb-checklist-question-area-wrapper">
    <div class="category-sortable-wrapper"><?php echo $questions; ?></div>
    <div class="clearfix"></div>
  </div>
  <div class="clearfix"></div>
</div>