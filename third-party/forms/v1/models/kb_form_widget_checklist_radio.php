<?php


/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */
class kb_form_widget_checklist_radio extends kb_form_widget{
  static $old_category = '';
  var $answers = null;

  public function __construct($settings = array()){
    parent::__construct($settings);
  }

  public function render_input(){
    parent::render_input();
    $this->settings['old_category'] = self::$old_category;
    $content = kb::view('checklist_radio', $this->settings);
    self::$old_category = $this->settings['category'];
    return $content;
  }


  public function classes(){
    $this->classes = parent::classes();
    $this->classes['checklist'] = 'checklist';
    $this->classes['radio']     = 'radio';
    return array_unique($this->classes);
  }


}