<?php


/**
 * Description of kb_form_widget_attachment
 *
 * @author kboydstu
 */
class kb_form_widget_attachment extends kb_form_widget_input_file{

  public function __construct($settings = array()){
    $upload_path = kb::config('attachment_temp_file_path');
    $upload_path = empty($upload_path) ? '/tmp/' : $upload_path;
    $settings['upload_path'] = isset($settings['upload_path']) ? $settings['upload_path'] : $upload_path;
    parent::__construct($settings);
  }
   public function set_value_form($value){

		$uploads = isset($value['uploads']) ? $value['uploads'] : null;
		if(!empty($uploads)){
			unset($value['uploads']);
      $file = array();
      $file['name'] = $uploads['name'];
      $file['type'] = $uploads['type'];
      $path_exists = file_exists($this->settings['upload_path']);
      if(!$path_exists){
        $path_exists = mkdir($this->settings['upload_path']);
      }
      if($path_exists){
        $new_file_path = $this->settings['upload_path'] . time() . '_' . $file['name'];
        $status           = move_uploaded_file($uploads['tmp_name'], $new_file_path);
        $file['path']     = kb::save_session_data($new_file_path);
        $file['size']     = $uploads['size'];
        $file['uploader'] = kb::client_uupic();
        $file['date']     = time();
      }
		}

    $this->form_value = $file;
    $this->_set_value_db($file);
  }


}