<?php

/**
 * Description of kb_form_widget
 *
 * @author biwana
 */
class kb_form_widget_input_lookup extends kb_form_widget_input {

  public $lookup_url = null;

  public function __construct($settings = array()) {
    $settings['widget'] = 'text'; // reset to text
    $this->set_parameters($settings);
    parent::__construct($settings);
  }

  static function add_css() {
    switch (BSG_FORM_VIEW_DIRECTORY) {
      case 'v1':
      default:
        kb::add_css('bsg-form-widgets/bsg.lookup.css');
        break;
    }
  }

  static function add_js() {
    switch (BSG_FORM_VIEW_DIRECTORY) {
      case 'v1':
      default:
        kb::add_js('bsg-form/bsg-form-widgets/bsg.lookup.js');
        break;
    }
  }

  public function render_input() {
    kb_form_widget_input_lookup::add_css();
    kb_form_widget_input_lookup::add_js();
    return parent::render_input();
  }

  public function set_parameters($settings) {
    $parameters = array();
    $parameters[] = ' title="' . (!empty($settings['title']) ? ($settings['title']) : 'Begin typing to perform lookup...') . '"';
    $parameters[] = ' populate="' . $settings['populate'] . '"';
    $parameters[] = ' lookup="' . $settings['lookup'] . '"';
    parent::attributes($parameters);
  }

  public function classes() {
    $this->classes = parent::classes();
    $this->classes['bsg-lookup'] = 'bsg-lookup';
    return array_unique($this->classes);
  }

  public function attributes() {
    $this->attributes = parent::attributes();
    return array_unique($this->attributes);
  }

}