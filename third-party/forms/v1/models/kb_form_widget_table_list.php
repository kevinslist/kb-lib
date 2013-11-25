<?php

/**
 * Description of kb_form_widget_table_list
 *
 * @author biwana
 */
class kb_form_widget_table_list extends kb_form_widget {

    public function __construct($settings = array()) {
        parent::__construct($settings);
    }

    public function set_value_db($value) {
        $this->db_value = $value;
        $this->_set_value_form($value);
    }

    public function set_value_form($value) {
        $form = $this->settings['form'];
        $this->form_value = $value;

        if (isset($this->form_value['existing']) && !empty($this->form_value['existing'])) {
            foreach ($this->form_value['existing'] as $primary_key => $sub_form_values) {
                $sub_form = new $form($this->settings['id']);
                $sub_form->values($sub_form_values, FALSE);
                $this->form_value['existing'][$primary_key] = $sub_form->values();
            }
        }
        $this->_set_value_db($this->form_value);
    }

    public function render() {
        kb::add_css('bsg-form-widgets/bsg.table_list.css');
//        kb::add_js('bsg-form/bsg-form-widgets/bsg.table_list.js');

        $this->settings['legend'] = $this->settings['label'];
        $form = $this->settings['form'];

        $sub_form = new $form($this->settings['id']);
        $ci_instance = get_instance();
        $ci_instance->load->library('table', array('id' => $this->settings['id'] .'-form-list', 'class' => 'bsg-table bsg-form-list-one-to-many'), 'form_list');

        $heading = array();
        foreach ($sub_form->field_definitions as $key => $field) {
            $heading[] = isset($field['label']) ? $field['label'] : '&nbsp;';
        }
        call_user_func_array(array($ci_instance->form_list, 'set_heading'), $heading);

        if (isset($this->form_value['existing']) && !empty($this->form_value['existing'])) {

            foreach ($this->form_value['existing'] as $primary_key => $values) {

                $sub_form = new $form($this->settings['id']);
                $sub_form->sub_form = true;
                $sub_form->sub_form_id = $primary_key;
                $row = array();
                $sub_form->set_values($values);
                foreach ($sub_form->layout as $key => $element) {
                    $sub_form->fields[$key]->settings['label'] = '';
                    $row[] = $sub_form->render_field($key, $sub_form->fields[$element], FALSE);
                }
                $ci_instance->form_list->add_row($row);
            }
        }
        if (isset($this->settings['tablesorter'])) {
            $ci_instance->form_list->add_tablesorter();
        }
        $this->settings['existing'] = $ci_instance->form_list->generate();
        return kb::view('table_list_fieldset', $this->settings);
    }

    function validate() {
        $error = false;
        $errors = array();
        $form = $this->settings['form'];

        if (isset($this->form_value['existing']) && !empty($this->form_value['existing'])) {
            foreach ($this->form_value['existing'] as $primary_key => $sub_form_values) {
                $sub_form = new $form($this->settings['id']);

                $sub_form->values($sub_form_values);
                $new_errors = $sub_form->validate();
                if (!empty($new_errors)) {
                    $errors[] = implode('<br />', $new_errors);
                }
            }
        }

        if (!empty($errors)) {
            $error = implode('<br />', $errors);
        }
        return $error;
    }

}