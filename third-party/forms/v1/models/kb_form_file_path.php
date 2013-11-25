<?php

class kb_form_file_path extends kb_form_widget_input {

  public function __construct($settings = array()) {
    parent::__construct($settings);
  }

  public function render_input(){
    parent::render_input();
    return kb::view('field_file_path', $this->settings);
  }


}