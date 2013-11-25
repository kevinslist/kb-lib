<?php

class kb_form_widget_approver_workflow extends kb_form_widget {

  var $workflow_settings = null;
  var $disposition_array = array();
  var $current_approver_type_id = 0;
  var $workflow_completed = FALSE;

  public function __construct($settings = array(), $form = null) {
    parent::__construct($settings, $form);
  }

  public function render($ajax = FALSE) {
    if (!$ajax) {
      kb::add_css('bsg-form-widgets/bsg.approver-workflow.css');
      kb::add_js('bsg-form/bsg-form-widgets/bsg.approver-workflow.js');
      kb_form_widget_input_userlookup::add_css();
      kb_form_widget_input_userlookup::add_js();
      kb_form_widget_input_userlookup_multi::add_css();
      kb_form_widget_input_userlookup_multi::add_js();
    }
    $value = $this->get_value(FALSE);
    $this->workflow_completed = empty($value) ? FALSE : $value->is_completed;
    $approvers = array();

    if (!empty($value) && is_array($value->approvers) && count($value->approvers)) {
      $this->current_approver_type_id = $value->get_next_approvers(TRUE);
      foreach ($value->approvers as $approver_type_id => $approver_type_data) {
        $approvers[] = $this->render_approver($approver_type_data);
      }
    }

    if (isset($this->settings['workflow_settings']['disposition']) && $this->settings['workflow_settings']['disposition'] && count($this->disposition_array)) {
      $disposition_settings = array(
          'options' => $this->disposition_array,
          'type' => 'text',
          'widget' => 'select',
          'label' => 'Disposition To',
          'id' => 'approver_workflow_disposition_to',
          'classes' => 'approver-workflow-dispotition-options',
      );
      $field = new kb_form_widget_select($disposition_settings);
      $field->set_value($value->disposition_to());

      $disposition_settings['content'] = $field->render();
      $approvers[] = kb::view('field_wrapper', $disposition_settings);
    }

    $vars['content'] = implode("\r", $approvers);
    $content = $ajax ? $vars['content'] : kb::view('ajax_load_wrapper', array_merge($this->settings, $vars));
    return $content;
  }

  public function render_approver($approver_type_data) {
    $settings       = array();
    $approved       = FALSE;
    $lookup         = FALSE;
    $multi_lookup   = FALSE;
    $multi_static   = FALSE;
    $dropdown       = FALSE;
    $readonly       = $this->get_readonly();

      // modify settings for various approver types
    switch ($approver_type_data['approver_type']['approver_widget']) {
      case APPROVER_TYPE_DROPDOWN:
        $dropdown = TRUE;
        kb::ci()->load->model('Approver_Type');
        $dropdown = TRUE;
        $settings['widget'] = 'select';
        $settings['bsg-user-lookup'] = TRUE;
        $settings['options'] = Approver_Type::get_dropdown_options($approver_type_data['approver_type']['approver_type_id']);
        $settings['initial'] = FALSE;
        break;
      case APPROVER_TYPE_MULTI_LOOKUP:
        $settings['fieldset'] = FALSE;
        $multi_lookup = TRUE;
        break;
      case APPROVER_TYPE_MULTI_STATIC:
        $multi_lookup = TRUE;
        $readonly = TRUE;
        $settings['fieldset'] = FALSE;
        break;
      case APPROVER_TYPE_ROLE_BASED:
        $multi_lookup = TRUE;
        $readonly = TRUE;
        $settings['fieldset'] = FALSE;
        break;
      case APPROVER_TYPE_CLIENT:
      case APPROVER_TYPE_STATIC:
        $lookup = TRUE;
        $readonly = TRUE;
        break;
      default:
        $lookup = TRUE;
        break;
    }

      // check if approved
    if (isset($approver_type_data['approvers']) && !empty($approver_type_data['approvers'])) {
      foreach ($approver_type_data['approvers'] as $u => $approver) {
        if (!empty($approver->values['approved_date'])) {
          kb::ci()->load->model('VwNedExtract');
          if(!$this->workflow_completed){
            $approver_disposition_name = $multi_lookup ? 'Group' : VwNedExtract::displayname('' . $approver->values['uupic']);
            $this->disposition_array[$approver_type_data['approver_type']['approver_type_id']] = $approver_type_data['approver_type']['approver_type_name'] . ': ' . $approver_disposition_name;
          }
          $approved = $approver->values['approved_date'];
          $readonly = TRUE;
          $settings['attributes']['data-approved-by-uupic'] = 'data-approved-by-uupic="'.$approver->values['uupic'].'"';
          $settings['attributes']['data-approved-date'] = 'data-approved-date="'.formatdatetime($approved).'"';
        }
      }
      if($multi_static || $multi_lookup){
        $uupic = array('existing' => array());
        foreach($approver_type_data['approvers'] as $u => $a){
          $a_key = empty($a->id) ? 'new' : 'existing';
          $uupic[$a_key][''.$u] = ''.$u;
        }
      }else{
        $uupic = reset(array_keys($approver_type_data['approvers']));
      }
    } else {
      $uupic = NULL;
    }

    $settings['id'] = $this->settings['id'] . '[' . $approver_type_data['approver_type']['approver_type_id'] . '][uupics][]';
    $settings['type'] = 'approver_workflow';
    $settings['label'] = $approver_type_data['approver_type']['approver_type_name'];
    $settings['required'] = TRUE;
    $settings['classes'] = 'bsg-approver' . (!$approved ? '' : ' approved');
    
    if ($approver_type_data['approver_type']['approver_type_id'] == $this->current_approver_type_id) {
      $settings['classes'] .= ' current';
      $readonly = TRUE;
    }
    $settings['readonly'] = ($readonly || $this->get_readonly()) ? 'readonly' : FALSE;

    if ($lookup) {
      $field = new kb_form_widget_input_userlookup($settings);
      $field->set_value($uupic);
    }elseif($multi_static){
       // kb::dump($approver_type_data);
    }elseif ($multi_lookup) {
      $field = new kb_form_widget_input_userlookup_multi($settings);
      //kb::dump($uupic);
      $field->set_value($uupic);
    } elseif ($dropdown) {
      $field = new kb_form_widget_select($settings);
      $field->set_value($uupic);
    }

    $settings['content'] = $field->render();
    $rendered_field = kb::view('field_wrapper', $settings);

    return $rendered_field;
  }

  public function set_value_form($value) {
    if(!empty($this->settings['workflow_settings']['category-id'])){
      $category_id = isset($_POST[$this->settings['workflow_settings']['category-id']]) ? $_POST[$this->settings['workflow_settings']['category-id']] : null;
    }elseif(!empty($this->settings['workflow_settings']['static-category-id'])){
      $category_id = $this->settings['workflow_settings']['static-category-id'];
    }
    kb::ci()->load->model('Approver_Workflow');
    //kb::ci()->Approver_Workflow->load_from_db($this->settings['workflow_settings']['object-id'], $category_id);
    //$default_value = kb::ci()->Approver_Workflow;

    $this->form_value = new Approver_Workflow($this->settings['workflow_settings']['object-id'], $category_id, $this->settings['workflow_settings']['object-class']);

    $this->form_value->set_values($this->settings['workflow_settings']['object-id'], $category_id, $value);
    if (kb::is_post() && isset($_POST['approver_workflow_disposition_to'])) {
      $this->form_value->disposition_to($_POST['approver_workflow_disposition_to']);
    }

    parent::set_value_form($this->form_value);
  }

  public function classes() {
    $this->classes = parent::classes();
    $this->classes[] = 'approver-workflow';
    return $this->classes;
  }

  public function attributes() {
    $this->attributes = parent::attributes();
    return $this->attributes;
  }
}