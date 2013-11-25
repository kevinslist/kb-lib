<?php


/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */
class kb_form_widget_textarea extends kb_form_widget{
  
  public function __construct($settings = array()){
      parent::__construct($settings);
			$this->settings['num_rows'] = isset($settings['num_rows']) ? (int)$settings['num_rows'] : 2;
			$this->settings['num_cols'] = isset($settings['num_cols']) ? (int)$settings['num_cols'] : 80;
  }
  
  public function render_input(){
    parent::render_input();
    return kb::view('field_textarea', $this->settings);
  }


  public function set_value_form($value){
    $text = bsg_clean_msword($value);
    $this->form_value = $text;
    $this->_set_value_db($text);

  }

  public function set_value_db($value){
    $text = bsg_clean_msword($value);
    $this->db_value = $text;
    $this->_set_value_form($text);
  }

  public function classes(){
    $classes = parent::classes();
    $classes[] = 'input';
    $classes[] = 'textarea';
    return $classes;
  }
  
  public function attributes(){
    $attributes = parent::attributes();
    $attributes[] = ' rows="' . $this->settings['num_rows'] . '" ';
    $attributes[] = ' cols="' . $this->settings['num_cols'] . '" ';
    
    return array_unique($attributes);
  }
  
}