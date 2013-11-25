<?php


/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */
class kb_form_widget_building_room_multi extends kb_form_widget{
  var $existing_locations = array();
	
  public function __construct($settings = array()){
    if(!isset($settings['initial']) || FALSE !== $settings['initial']){
      $settings['initial'] = empty($settings['initial']) ? 'Select One' : $settings['initial'];
    }
    parent::__construct($settings);
  }

  static function add_css() {
    switch (BSG_FORM_VIEW_DIRECTORY) {
      case 'v1':
      default:
        kb::add_css('bsg-form-widgets/bsg.multi-building-room.css');
        break;
    }
  }

  static function add_js() {
    switch (BSG_FORM_VIEW_DIRECTORY) {
      case 'v1':
      default:
        kb::add_js('bsg-form/bsg-form-widgets/bsg.multi-building-room.js');
        break;
    }
  }
  
  public function render(){
    kb_form_widget_building_room_multi::add_css();
    kb_form_widget_building_room_multi::add_js();
    
    $this->settings['classes']		= implode(' ', $this->classes());
    $this->settings['attributes'] = implode(' ', $this->attributes());
    $this->settings['full_label'] = $this->label();
    $this->settings['form_value'] = $this->get_value(false);
    $this->settings['content']		= $this->render_input();
		
    return kb::view('addmore_fieldset', $this->settings);
  }
	

  public function render_input(){
    return kb::view('multi_building_room', $this->settings);

  }
}