<?php

/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */
class kb_form_widget_input_date extends kb_form_widget_input {

  public $lookup_url = null;

  public function __construct($settings = array()) {
    $settings['widget'] = 'text'; // reset to text
    parent::__construct($settings);
    $this->settings['has_datepicker'] = isset($settings['has_datepicker']) ? $settings['has_datepicker'] : TRUE;
    $this->settings['has_icon'] = isset($settings['has_icon']) ? $settings['has_icon'] : TRUE;
    $this->settings['range_from'] = isset($settings['range_from']) ? $settings['range_from'] : '-50Y';
    $this->settings['range_to'] = isset($settings['range_to']) ? $settings['range_to'] : '+35Y';
    $this->settings['year_range'] = isset($settings['year_range']) ? $settings['year_range'] : 'c-100:c+20';
    

    if (isset($this->settings['greater-than-field'])) {
      $this->classes[] = 'bsg-date-compare-greater-than-field';
      $this->attributes['data-greater-than-field'] = 'data-greater-than-field="' . $this->settings['greater-than-field'] . '"';     
    }
  }

  static function add_css() {
    switch (BSG_FORM_VIEW_DIRECTORY) {
      case 'v1':
      default:
        kb::add_css('bsg-form-widgets/bsg.date.css');
        break;
    }
  }

  static function add_js() {
    switch (BSG_FORM_VIEW_DIRECTORY) {
      case 'v1':
      default:
        kb::add_js('bsg-form/bsg-form-widgets/bsg.date.js');
        break;
    }
  }

  public function render_input() {
    kb_form_widget_input_date::add_css();
    kb_form_widget_input_date::add_js();    
    return parent::render_input();
  }

  public function set_value_db($value) {
    $this->db_value = empty($value) ? null : (int) $value;
    $v = empty($this->db_value) ? '' : format($value);
    $this->_set_value_form($v);
  }

  public function _set_value_db($value) {
    $this->db_value = $value;
  }

  public function set_value_form($value) {
    $this->form_value = $value;
    $v = empty($value) ? null : strtotime($value);
    $this->_set_value_db($v);
  }

  public function _set_value_form($value) {
    $this->form_value = $value;
  }

  public function classes() {
    $classes = parent::classes();
    $classes[] = 'bsg-date';
    if ($this->settings['has_datepicker']) {
      $classes[] = 'has-datepicker';
    }
    if ($this->settings['has_icon']) {
      $classes[] = 'has-icon';
    }    
    if(isset($this->settings['greater-than-field']) && $this->get_readonly()){
        $classes[] = 'readonly-and-greater-than-field';
    }
    return $classes;
  }

  public function attributes() {
    $attributes = parent::attributes();
    $attributes[] = ' data-range-from="' . $this->settings['range_from'] . '"';
    $attributes[] = ' data-range-to="' . $this->settings['range_to'] . '"';
    $attributes[] = ' data-year-range="' . $this->settings['year_range'] . '"';

    return array_unique($attributes);
  }

}