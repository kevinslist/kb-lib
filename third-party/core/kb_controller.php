<?php

if (!defined('BASEPATH')){  exit('No direct script access allowed'); }

class kb_controller extends CI_Controller {
  
  public function render_page(){
    print $this->load->view('layouts/default_layout');
  }


}