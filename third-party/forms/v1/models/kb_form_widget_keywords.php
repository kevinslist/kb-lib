<?php


/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */
class kb_form_widget_keywords extends kb_form_widget{
	public $lookup_url = null;

  public function __construct($settings = array()){
			$settings['widget'] = 'text'; // reset to text
      parent::__construct($settings);
  }


  public function render(){
    kb::add_css('bsg-form-widgets/bsg.keywords.css');
    kb::add_js('bsg-form/bsg-form-widgets/bsg.keywords.js');

    $this->settings['classes']		= implode(' ', $this->classes());
    $this->settings['attributes'] = implode(' ', $this->attributes());
    $this->settings['full_label'] = $this->label();
    $this->settings['form_value'] = $this->get_value(false);

		$vars = $this->settings;
		$vars['id'] = $this->settings['id'] . '-fake';
		$vars['form_value'] = '';
		$this->settings['input_content'] = kb::view('field_input', $vars);

    return kb::view('keywords_fieldset', $this->settings);
  }


  public function classes(){
    $classes = parent::classes();
    $classes[] = 'bsg-keywords';
    return $classes;
  }


}