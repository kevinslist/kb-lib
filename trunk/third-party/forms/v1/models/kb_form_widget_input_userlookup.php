<?php


/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */
class kb_form_widget_input_userlookup extends kb_form_widget_input{
	public $lookup_url = null;

  public function __construct($settings = array()){
			$settings['widget'] = 'text'; // reset to text
      parent::__construct($settings);
      $filter = '';
      if(isset($settings['filter'])){
        $filter = base64_encode(serialize($settings['filter']));
      }
      $this->attributes['data-bsg-user-lookup-filter'] = 'data-bsg-user-lookup-filter="' . $filter . '"';
  }

  static function add_css(){
    kb::add_css('bsg-form-widgets/bsg.user-lookup.css');
  }
  static function add_js(){
    kb::add_js('bsg-form/bsg-form-widgets/bsg.user-lookup.js');
  }
  
  public function render_input(){
    kb_form_widget_input_userlookup::add_css();
    kb_form_widget_input_userlookup::add_js();
    return parent::render_input();

  }


  public function classes(){
    $this->classes = parent::classes();
    $this->classes['bsg-user-lookup'] = 'bsg-user-lookup';
    return array_unique($this->classes);
  }

  public function attributes(){
    $this->attributes = parent::attributes();
    $this->attributes['lookup_title'] = ' title="Begin typing (lastname, firstname) to perform lookup..."';
    return array_unique($this->attributes);
  }


}