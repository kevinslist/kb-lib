<?php

/**
 * Description of kb_form_widget_one_to_many
 *
 * @author kboydstu
 */

class kb_form_widget_one_to_many extends kb_form_widget {
  
  
  public function __construct($settings = array()){
    parent::__construct($settings);
    $this->settings['can_delete_item'] = (isset($this->settings['can_delete_item']) && FALSE === $this->settings['can_delete_item']) ? FALSE : TRUE;
    $this->settings['new_item_on_init'] = (isset($this->settings['new_item_on_init']) && FALSE === $this->settings['new_item_on_init']) ? FALSE : TRUE;
    
  }

  public function set_value_db($value){
    $this->db_value = $value;
    $this->_set_value_form($value);
  }

  public function set_value_form($value){
    $form = $this->settings['form'];
    $this->form_value = $value;

    if(isset($this->form_value['existing']) && !empty($this->form_value['existing'])){
      foreach($this->form_value['existing'] as $primary_key => $sub_form_values){
        $sub_form = new $form($this->settings['id']);
        $sub_form->values($sub_form_values, FALSE);
        $this->form_value['existing'][$primary_key] = $sub_form->values();
      }
    }

    if(isset($this->form_value['new']) && !empty($this->form_value['new'])){
      foreach($this->form_value['new'] as $key => $sub_form_values){
        $sub_form = new $form($this->settings['id']);
        $sub_form->values($sub_form_values, FALSE);
        $this->form_value['new'][$key] = $sub_form->values();
      }
    }
    $this->_set_value_db($this->form_value);
  }
  
  public function render(){
    kb::add_css('bsg-form-widgets/bsg.one-to-many.css');
    kb::add_js('bsg-form/bsg-form-widgets/bsg.one-to-many.js');

    $this->settings['legend'] = $this->settings['label'];
    $form                     = $this->settings['form'];

    $clone                    = new $form($this->settings['id']);
    $this->settings['clone_content']  = $clone->render_sub_form($this->readonly, TRUE, '_cl0ne_');


    $existing_items = array();
    $new_items = array();

    if(isset($this->form_value['existing']) && !empty($this->form_value['existing'])){
      foreach($this->form_value['existing'] as $primary_key => $sub_form_values){
        $sub_form = new $form($this->settings['id']);
        $sub_form->values($sub_form_values);
        $existing_items[] = $sub_form->render_sub_form($this->readonly, FALSE, $primary_key);
      }
    }
    $this->settings['existing_items']        = $existing_items;

    if(isset($this->form_value['new']) && !empty($this->form_value['new'])){
      foreach($this->form_value['new'] as $sub_form_values){
        $sub_form = new $form($this->settings['id']);
        $sub_form->values($sub_form_values);
        $new_items[] = $sub_form->render_sub_form($this->readonly, TRUE);
      }
    }
    $this->settings['new_items']        = $new_items;
    
    $this->settings['delete_warn'] = isset($this->settings['delete_warn']) && $this->settings['delete_warn'] ? 'delete_warn' : '';

    return kb::view('one_to_many_fieldset', $this->settings);
  }

  function validate(){
    $error  = false;
    $errors = array();
    $form   = $this->settings['form'];

    if(isset($this->form_value['existing']) && !empty($this->form_value['existing'])){
      foreach($this->form_value['existing'] as $primary_key => $sub_form_values){
        $sub_form = new $form($this->settings['id']);

        $sub_form->values($sub_form_values);
        $new_errors = $sub_form->validate();
        if(!empty($new_errors)){
          $errors[] = implode('<br />', $new_errors);
        }
      }
    }

    if(isset($this->form_value['new']) && !empty($this->form_value['new'])){
      foreach($this->form_value['new'] as $sub_form_values){
        $sub_form = new $form($this->settings['id']);
        $sub_form->values($sub_form_values);
        $new_errors = $sub_form->validate();
        if(!empty($new_errors)){
          $errors[] = implode('<br />', $new_errors);
        }
      }
    }
    if(!empty($errors)){
      $error = implode('<br />', $errors);
    }
    return $error;
  }

}