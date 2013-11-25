<?php


/**
 * Description of kb_form_widget_attachment
 *
 * @author kboydstu
 */
class kb_form_widget_camera_image extends kb_form_widget{

  public function __construct($settings = array()){
    $upload_path = kb::config('attachment_temp_file_path');
    $upload_path = empty($upload_path) ? '/tmp/' : $upload_path;
    $settings['upload_path'] = isset($settings['upload_path']) ? $settings['upload_path'] : $upload_path;
    parent::__construct($settings);
  }
  public function render_label(){
    return '';
  }

  public function render_input(){
    $this->settings['form_value'] = $this->get_value();
    $this->settings['image_src'] = !empty($this->settings['form_value']) ? 'data:image/png;base64,' . $this->settings['form_value'] : '';
    parent::render_input();
    return kb::view('field_bsg_camera_image', $this->settings);
  }

  public function attributes(){
    $this->attributes = parent::attributes();
    //kb::dump($this->settings);
    $this->attributes['data-image-width'] = 'data-image-width="' . $this->settings['image-width'] . '"';
    $this->attributes['data-image-height'] = 'data-image-height="' . $this->settings['image-height'] . '"';
    return array_unique($this->attributes);
  }




   public function set_value_form2($value){

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