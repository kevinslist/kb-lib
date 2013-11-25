<fieldset class="container <?php echo $classes;?>" id="fieldset-<?php echo $id;?>" data-id="<?php echo $id;?>">
  <legend id="legend-<?php echo $id;?>" class="kb-userlookup-muti-legend"><?php echo $full_label; ?></legend>
	<?php if(!$readonly): ?>
  <div class="new-file-upload highlighted-file-upload-wrapper">
		<label class="new-file-label" for="<?php echo $id;?>"><?php echo $label_new_attachment; ?></label>
		<input class="field-input kb-attachment kb-attachment-multi" type="file" id="<?php echo $id;?>" name="<?php echo $id;?>[new][]" value="" />
		<?php if(isset($form_value['new'])): ?>
		<div id="<?php echo $id; ?>_wrap_list_existing"  class="MultiFile-list kb-multi-attachment">
		<?php foreach($form_value['new'] as $new_file_id=>$new_file): ?>

			<div class="MultiFile-label" id="MultiFile-label-<?php echo $new_file_id; ?>">
				<a href="#attachments_wrap" class="MultiFile-remove">x</a> <span title="File selected: <?php echo htmlentities($new_file['name']); ?>" class="MultiFile-title"><?php echo htmlentities($new_file['name']); ?></span>
					<input type="hidden" name="<?php echo $id;?>[new][<?php echo $new_file_id;?>][name]" value="<?php echo htmlentities($new_file['name']); ?>" />
					<input type="hidden" name="<?php echo $id;?>[new][<?php echo $new_file_id;?>][path]" value="<?php echo htmlentities($new_file['path']); ?>" />
					<input type="hidden" name="<?php echo $id;?>[new][<?php echo $new_file_id;?>][size]" value="<?php echo htmlentities($new_file['size']); ?>" />
					<input type="hidden" name="<?php echo $id;?>[new][<?php echo $new_file_id;?>][type]" value="<?php echo htmlentities($new_file['type']); ?>" />
			</div>
		<?php endforeach; ?>
		</div>
		<?php endif; ?>
		<div class="clearfix"></div>
	</div>
	<?php endif; ?>
	<div class="file-groups">
		<?php if(isset($form_value['existing'])): ?>
		<?php $total_files = count($form_value['existing']); ?>
		<div class="existing-file-wrapper" data-total-files="<?php echo $total_files; ?>">
			<?php foreach($form_value['existing'] as $file_id=>$file_group_files):
				$total_versions = count($file_group_files);
				$new_file = null;
				$did_group_header = false;
			?>
			<fieldset id="fieldset-existing_<?php echo $id; ?>_<?php echo $file_id; ?>" class="file-group-wrapper existing" data-total-versions="<?php echo $total_versions; ?>">
				<?php if (!$noversion) { // needed for multi-version only?>
        <legend>File: <?php echo $file_id; ?><?php if($delete && !$readonly): ?><a class="delete-item delete-all-versions" href="#" title="<?php echo 'Delete all versions for File: ' . $file_id; ?>">delete</a><?php endif; ?></legend>
        <?php }?>
				<div class="old-files">
				<?php
					foreach($file_group_files as $attachment_id=>$attachment):

						if('new' == $attachment_id){
								$new_file = $attachment;
						}else{
							if(!$did_group_header){
								kb::ci()->load->library('table');
								$table = new bsg_table();
								$table->set_id('fieldset-existing-' . $id . '-' . $file_id);
								$table->set_class('file-group-table kb-table');
								$heading = array('File Name', 'Version', 'Date', 'User');

								if($delete && (!$readonly || $noversion )){
									$table->set_heading('File Name', 'Version', 'Date', 'User', 'Delete');
								}else{
									$table->set_heading('File Name', 'Version', 'Date', 'User');
								}
								$did_group_header = true;
							}
							$row = array();              
							$link = '<a href="' . site_url($download_path . $attachment_id) . '" title="' . htmlentities($attachment['attachment_file_name']) . '">' . htmlentities($attachment['attachment_file_name']) . '</a>';
							$row[] = array('data'=>$link, 'class'=>'file-name');
							$row[] = array('data'=>htmlentities($attachment['attachment_file_version']), 'class'=>'file-version');
							$row[] = array('data'=>formatdatetime($attachment['attachment_file_date_time']), 'class'=>'file-date-time');
							$row[] = array('data'=>VwNedExtract::displayauid($attachment['approver_uupic']), 'class'=>'file-user', 'title' =>VwNedExtract::displayname($attachment['approver_uupic']));
              
								if($delete && (!$readonly || $noversion)){
									$row[] = array('data'=>'<a href="#" class="delete-item delete-attachment-version-item" data-attachment-id="'. $attachment_id . '" title="Delete version: ' . $attachment['attachment_file_version'] . '">X</a>', 'class'=>'file-delete');
								}
							$table->add_row($row);
					?>
					<div id="file-info-<?php echo $attachment_id; ?>" class="file-info-hidden">
						<input type="hidden" name="<?php echo $id;?>[existing][<?php echo $file_id;?>][<?php echo $attachment_id;?>][attachment_file_name]" value="<?php echo htmlentities($attachment['attachment_file_name']); ?>" />
						<input type="hidden" name="<?php echo $id;?>[existing][<?php echo $file_id;?>][<?php echo $attachment_id;?>][attachment_file_size]" value="<?php echo htmlentities($attachment['attachment_file_size']); ?>" />
						<input type="hidden" name="<?php echo $id;?>[existing][<?php echo $file_id;?>][<?php echo $attachment_id;?>][attachment_file_type]" value="<?php echo htmlentities($attachment['attachment_file_type']); ?>" />
						<input type="hidden" name="<?php echo $id;?>[existing][<?php echo $file_id;?>][<?php echo $attachment_id;?>][attachment_file_date_time]" value="<?php echo htmlentities($attachment['attachment_file_date_time']); ?>" />
						<input type="hidden" name="<?php echo $id;?>[existing][<?php echo $file_id;?>][<?php echo $attachment_id;?>][attachment_file_version]" value="<?php echo htmlentities($attachment['attachment_file_version']); ?>" />
						<input type="hidden" name="<?php echo $id;?>[existing][<?php echo $file_id;?>][<?php echo $attachment_id;?>][approver_uupic]" value="<?php echo $attachment['approver_uupic']; ?>" />
            <?php if(isset($attachment['approver_type'])): ?>
						<input type="hidden" name="<?php echo $id;?>[existing][<?php echo $file_id;?>][<?php echo $attachment_id;?>][approver_type]" value="<?php echo $attachment['approver_type']; ?>" />
            <?php endif; ?>
					</div>

			<?php }; ?>
			<?php endforeach; ?>
			<?php if($did_group_header): ?>
			<?php echo $table->generate(); ?>
			<?php endif; ?>
			</div>
			<?php if(!$readonly && !$noversion): ?>
			<div class="new-version-upload-wrapper highlighted-file-upload-wrapper">
				<?php if(!empty($new_file)): ?>
				<?php $has_new_class="has-new"; ?>
					<div class="file-info-wrapper new-file" data-version="new">
						<div class="file-label file-column"><label>New Version</label></div>
						<div class="file-delete file-column"><a href="#" class="delete-file-link">X</a></div>
						<div class="file-name file-column">
							<?php echo htmlentities($new_file['attachment_file_name']); ?>
						</div>
						<input type="hidden" name="<?php echo $id;?>[existing][<?php echo $file_id;?>][new][attachment_file_name]"		value="<?php echo htmlentities($new_file['attachment_file_name']); ?>" />
						<input type="hidden" name="<?php echo $id;?>[existing][<?php echo $file_id;?>][new][attachment_file_size]"		value="<?php echo htmlentities($new_file['attachment_file_size']); ?>"  />
						<input type="hidden" name="<?php echo $id;?>[existing][<?php echo $file_id;?>][new][attachment_file_type]"		value="<?php echo htmlentities($new_file['attachment_file_type']); ?>"  />
						<input type="hidden" name="<?php echo $id;?>[existing][<?php echo $file_id;?>][new][attachment_file_path]"		value="<?php echo htmlentities($new_file['attachment_file_path']); ?>" />
						<div class="clearfix"></div>
					</div>
				<?php else: ?>
				<?php $has_new_class = ''; ?>
				<?php endif; ?>
				<div class="input-control <?php echo $has_new_class;?>">
					<label for="<?php echo 'new-' . $id . '-' . $file_id;?>">New Version</label> <input type="file" id="<?php echo 'new-' . $id . '-' . $file_id;?>" class="new-version-upload" name="<?php echo $id;?>[existing][<?php echo $file_id;?>][new]" value="" />
				</div>
			</div>
			<?php endif; ?>
		</fieldset>
		<?php endforeach; ?>
		</div>
		<?php endif; ?>
	</div>
</fieldset>
