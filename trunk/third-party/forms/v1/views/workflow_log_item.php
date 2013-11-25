<div class="workflow-log-item <?php echo $class;?>" id="workflow-log-item-<?php echo $log_id;?>">
	<div class="workflow-item-header">
		<div class="log-column log-date"><?php echo $action_date; ?></div>
		<div class="log-column log-approver-type" title="<?php echo VwNedExtract::displayname($uupic); ?>">
			<?php echo $approver_name; ?> ( <span class="log-column log-user"><?php echo VwNedExtract::displayauid($uupic);?></span> )
		</div>
		<div class="log-column log-action"><?php echo $action; ?></div>
		<div class="clearfix"></div>
	</div>
	<div class="log-column log-comments"><?php echo htmlentities($comment); ?></div>
	<div class="clearfix"></div>
</div>