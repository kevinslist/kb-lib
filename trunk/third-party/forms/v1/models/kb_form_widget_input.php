<?php


/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */
class kb_form_widget_input extends kb_form_widget{

  public function __construct($settings = array()){
      parent::__construct($settings);
  }

  public function render_input(){
    parent::render_input();
    return kb::view('field_input', $this->settings);
  }


  public function classes(){
    $this->classes = parent::classes();
    $this->classes['input-field'] = 'input';
    return array_unique($this->classes);
  }


}