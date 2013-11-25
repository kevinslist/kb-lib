<?php


/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */
class kb_form_widget_input_button extends kb_form_widget_input{
  public function __construct($settings = array()){
      parent::__construct($settings);
  }

  public function render_label(){
    return '';
  }
  public function render_input(){
		parent::render_input();
    $content = kb::view('field_button', $this->settings);
    return $content;
  }


  public function classes(){
    $classes = parent::classes();
		if($this->reversed()){
			$classes[] = 'input-button';
		}
    return $classes;
  }

}