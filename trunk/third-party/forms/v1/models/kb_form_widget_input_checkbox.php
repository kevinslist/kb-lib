<?php

/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */
class kb_form_widget_input_checkbox extends kb_form_widget_input {

  public function __construct($settings = array()) {
    parent::__construct($settings);
  }

  public function get_value_db() {
    return empty($this->db_value) ? 0 : 1;
  }

  static function add_css() {
    switch (BSG_FORM_VIEW_DIRECTORY) {
      case 'v1':
      default:
        kb::add_css('bsg-form-widgets/bsg.input.checkbox.css');
        break;
    }
  }

  static function add_js() {
    switch (BSG_FORM_VIEW_DIRECTORY) {
      case 'v1':
      default:
        //kb::add_js('');
        break;
    }
  }

  public function render(){
    $this->settings['classes'] = implode(' ', $this->classes());
    $this->settings['attributes'] = implode(' ', $this->attributes());

    $this->settings['full_label'] = $this->label();
    $this->settings['form_value'] = $this->get_value(false);



    return kb::view('field_checkbox', $this->settings);
    /*
    if( $this->reversed() ){
      $out = $input . $label;
    }else{
      $out = $label . $input;
    }


    return $out;
     *
     */
  }

  public function render_input22() {
    kb_form_widget_input_checkbox::add_css();
    parent::render_input();
    return kb::view('field_checkbox', $this->settings);
  }

  public function attributes() {
    $this->attributes = parent::attributes();
    if ((int) $this->get_value()) {
      $this->attributes[] = ' checked="checked"';
    }
    if ($this->get_readonly()) {
      $this->attributes[] = ' disabled="disabled"';
    }

    return array_unique($this->attributes);
  }

  public function label($value = null) {
    $ret = $this;
    if (is_null($value)) {
      if (empty($this->settings['label'])) {
        $ret = '';
      } else {
        $ret = $this->settings['label'];
        if (!$this->reversed()) {
          $ret .= $this->settings['label_suffix'];
        }
      }
    } else {
      $this->settings['label'] = $value;
    }

    return $ret;
  }

  public function classes() {
    $classes = parent::classes();
   
    if ($this->reversed()) {
      $classes[] = 'checkbox-reversed';
    }
    return $classes;
  }

  public function reversed($value = null) {
    return !parent::reversed($value);
  }

}