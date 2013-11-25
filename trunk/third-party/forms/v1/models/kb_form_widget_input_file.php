<?php


/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */
class kb_form_widget_input_file extends kb_form_widget_input{
  public function __construct($settings = array()){
      $settings['widget'] = 'file';
      parent::__construct($settings);
  }
}