<?php


/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */
class kb_form_widget_input_userlookup_multi extends kb_form_widget_input_userlookup{
	public $lookup_url = null;

  public function __construct($settings = array()){
			$settings['widget'] = 'text'; // reset to text
      $settings['fieldset'] = isset($settings['fieldset']) && FALSE === $settings['fieldset'] ? FALSE : TRUE;
      parent::__construct($settings);
  }
  
  static function add_css(){
    switch(BSG_FORM_VIEW_DIRECTORY){
      case 'v1':
      default:
          kb::add_css('bsg-form-widgets/bsg.user-lookup.css');
          kb::add_css('bsg-form-widgets/bsg.user-lookup-multi.css');
          break;
    }
  }
  static function add_js(){
    switch(BSG_FORM_VIEW_DIRECTORY){
      case 'v1':
      default:
          kb::add_js('bsg-form/bsg-form-widgets/bsg.user-lookup.js');
          kb::add_js('bsg-form/bsg-form-widgets/bsg.user-lookup-multi.js');
          break;
    }
  }

  public function render(){
    kb_form_widget_input_userlookup_multi::add_css();
    kb_form_widget_input_userlookup_multi::add_js();
		kb::ci()->load->model('VwNedExtract');
    $this->settings['classes']		= implode(' ', $this->classes());
    $this->settings['attributes'] = implode(' ', $this->attributes());
    $this->settings['full_label'] = $this->label();
    $this->settings['form_value'] = $this->get_value(false);
    $this->settings['content']		= $this->render_input();
    
    if($this->settings['fieldset']){
      $widget_content = kb::view('userlookup_muti_fieldset', $this->settings);
    }else{
      $this->settings['content']		= parent::render();
      $widget_content = kb::view('userlookup_muti_divfield', $this->settings);
    }
    return $widget_content;
  }


  public function render_input(){
		$vars = $this->settings;
		$vars['id'] = $this->settings['id'] . '-fake';
		$vars['form_value'] = '';
		$this->settings['input_content'] = kb::view('field_input', $vars);
    return kb::view('userlookup_multi_content', $this->settings);

  }

  public function classes(){
    $classes = parent::classes();
    $classes[] = 'bsg-user-lookup-multi';
    return $classes;
  }


}