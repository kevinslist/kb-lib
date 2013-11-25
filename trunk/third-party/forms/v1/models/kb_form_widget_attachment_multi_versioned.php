<?php

/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */
class kb_form_widget_attachment_multi_versioned extends kb_form_widget {

  public $lookup_url = null;

  public function __construct($settings = array()) {
    kb::ci()->load->model('VwNedExtract');
    $upload_path = kb::config('attachment_temp_file_path');
    $upload_path = empty($upload_path) ? '/tmp/' : $upload_path;
    $settings['upload_path'] = isset($settings['upload_path']) ? $settings['upload_path'] : $upload_path;
    $settings['label_new_attachment'] = isset($settings['label_new_attachment']) ? $settings['label_new_attachment'] : 'Add New Attachment';
    $settings['delete'] = isset($settings['delete']) && TRUE === $settings['delete'] ? TRUE : FALSE;
    parent::__construct($settings);
  }

  static function add_css() {
    switch (BSG_FORM_VIEW_DIRECTORY) {
      case 'v1':
      default:
        kb::add_css('bsg-form-widgets/bsg.attachment-multi-versioned.css');
        break;
    }
  }

  static function add_js() {
    switch (BSG_FORM_VIEW_DIRECTORY) {
      case 'v1':
      default:
        kb::add_js('jquery/jquery.multifile.js');
        kb::add_js('bsg-form/bsg-form-widgets/bsg.attachment-multi-versioned.js');
        break;
    }
  }

  public function render() {
    kb_form_widget_attachment_multi_versioned::add_css();
    kb_form_widget_attachment_multi_versioned::add_js();

    $this->settings['classes'] = implode(' ', $this->classes());
    $this->settings['attributes'] = implode(' ', $this->attributes());
    $this->settings['full_label'] = $this->label();
    $this->settings['form_value'] = $this->get_value(false);

    return kb::view('attachment_muti_versioned_fieldset', $this->settings);
  }

  public function can_delete($delete = NULL) {
    $ret = $this;
    if (!is_null($delete)) {
      $this->settings['delete'] = $delete ? TRUE : FALSE;
    } else {
      $ret = $this->settings['delete'];
    }
    return $ret;
  }

  public function set_value_form($value) {

    $uploads = isset($value['uploads']) ? $value['uploads'] : null;
    if (!empty($uploads)) {
      unset($value['uploads']);
      foreach ($uploads['name'] as $type => $data) {
        switch ($type) {
          case 'new':
            $value['new'] = isset($value['new']) ? $value['new'] : array();

            foreach ($data as $key => $new_file_name) {
              $new_file_count = count($value['new']);
              $new_file_key = $new_file_count . '_' . $key;
              $file = array();
              $file['name'] = $new_file_name;
              $file['type'] = $uploads['type']['new'][$key];

              if (!file_exists($this->settings['upload_path'])) {
                $suc = mkdir($this->settings['upload_path']);
              }

              $new_file_path = $this->settings['upload_path'] . time() . '_' . $file['name'];
              $status = move_uploaded_file($uploads['tmp_name']['new'][$key], $new_file_path);
              $file['path'] = kb::save_session_data($new_file_path);
              $file['size'] = $uploads['size']['new'][$key];
              $file['version'] = 0;
              $file['uploader'] = kb::client_uupic();
              $value['new'][$new_file_key] = $file;
            }
            break;
          case 'existing':

            foreach ($data as $file_id => $new_file_name) {
              if (isset($new_file_name['new'])) {
                // add new version
                $attachment_file_name = $new_file_name['new'];
                $value['existing'][$file_id] = isset($value['existing'][$file_id]) ? $value['existing'][$file_id] : array();
                $new_file_version = count($value['existing'][$file_id]) + 1;
                $file = array();
                $file['attachment_file_name'] = $attachment_file_name;
                $file['attachment_file_type'] = $uploads['type']['existing'][$file_id]['new'];

                if (!file_exists($this->settings['upload_path'])) {
                  $suc = mkdir($this->settings['upload_path']);
                }

                $new_file_path = $this->settings['upload_path'] . time() . '_' . $file['attachment_file_name'];
                $status = move_uploaded_file($uploads['tmp_name']['existing'][$file_id]['new'], $new_file_path);
                $file['attachment_file_path'] = kb::save_session_data($new_file_path);
                $file['attachment_file_size'] = $uploads['size']['existing'][$file_id]['new'];
                $file['attachment_file_version'] = 0;
                $value['existing'][$file_id]['new'] = $file;
              }
            }
            break;
        }
      }
    }

    $this->form_value = $value;
    $this->_set_value_db($value);
  }

  public function classes() {
    $classes = parent::classes();
    $classes[] = 'bsg-attachment-multi-versioned';
    return $classes;
  }

}