<?php


/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */
class kb_form_widget_fieldset_text extends kb_form_widget{

  public function __construct($settings = array()){
      parent::__construct($settings);
  }

  public function render(){
		parent::render();
    return kb::view('field_fieldset_text', $this->settings);
  }

	function get_value($db_format = TRUE){
		return isset($this->settings['text']) ? $this->settings['text'] : NULL;
	}
	function set_value($text = '', $db_format = TRUE){
		$this->settings['text'] = $text;
		return $this;
	}

  public function classes(){
    $this->classes = parent::classes();
    $this->classes[] = 'fieldset-text';
    return $this->classes;
  }

}