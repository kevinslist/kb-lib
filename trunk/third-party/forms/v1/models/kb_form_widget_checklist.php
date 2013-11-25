<?php

/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */
class kb_form_widget_checklist extends kb_form_widget {

  public $checklist = NULL;

  public function __construct($settings = array(), $form = null) {
    $settings['load_trigger'] = isset($settings['load_trigger']) && $settings['load_trigger'] ? $settings['load_trigger'] : FALSE;
    parent::__construct($settings);
  }

  static function add_css() {
    switch (BSG_FORM_VIEW_DIRECTORY) {
      case 'v1':
      default:
        kb::add_css('bsg-form-widgets/bsg.checklist-widget.css');
        break;
    }
  }

  static function add_js() {
    switch (BSG_FORM_VIEW_DIRECTORY) {
      case 'v1':
      default:
        kb::add_js('bsg-form/bsg-form-widgets/bsg.checklist-widget.js');
        break;
    }
  }

  public function render($ajax = FALSE) {
    kb_form_widget_checklist::add_css();
    kb_form_widget_checklist::add_js();

    $this->settings['classes'] = implode(' ', $this->classes());
    $this->settings['attributes'] = implode(' ', $this->attributes());
    $checklist = $this->get_value();
    if (empty($checklist)) {
      $checklist = $this->set_value_form(NULL);
    }

    $this->settings['content'] = empty($checklist) ? '' : $checklist->render($this->settings);
    $out = $ajax ? $this->settings['content'] : kb::view('checklist_widget_wrapper', $this->settings);
    return $out;
  }

  public function set_empty_value() {
    $object_id = NULL;
    if (isset($this->settings['object_id']) && !empty($this->settings['object_id'])) {
      $object_id = $this->settings['object_id'];
    }
    $checklist_id = NULL;
    if (isset($this->settings['checklist_id']) && !empty($this->settings['checklist_id'])) {
      $checklist_id = $this->settings['checklist_id'];
    }
    $checklist_class = $this->settings['checklist_class'];
    $this->form_value = new $checklist_class;
  }

  public function set_value_form($value) {
    $object_id = NULL;
    if (isset($this->settings['object_id']) && !empty($this->settings['object_id'])) {
      $object_id = $this->settings['object_id'];
    } elseif (isset($this->settings['object_id_field']) && !empty($this->settings['object_id_field']) && isset($_POST[$this->settings['object_id_field']]) && !empty($_POST[$this->settings['object_id_field']])) {
      $object_id = $_POST[$this->settings['object_id_field']];
    }

    $checklist_id = NULL;
    if (isset($this->settings['checklist_id']) && !empty($this->settings['checklist_id'])) {
      $checklist_id = $this->settings['checklist_id'];
    } elseif (isset($this->settings['load_trigger']) && !empty($this->settings['load_trigger']) && isset($_POST[$this->settings['load_trigger']]) && !empty($_POST[$this->settings['load_trigger']])) {
      $checklist_id = $_POST[$this->settings['load_trigger']];
    }

    $checklist_class = $this->settings['checklist_class'];
    $this->form_value = new $checklist_class;


    //kb::ci()->load->model($checklist_class);
    $this->form_value->init_post($value, $object_id, $checklist_id);
    //$this->form_value = kb::ci()->$checklist_class;
    parent::set_value_form($this->form_value);
    return $this->form_value;
  }

}