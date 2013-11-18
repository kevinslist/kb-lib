<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class kb_loader extends CI_Loader {
  public function __construct() {
    parent::__construct();
    $this->_ci_view_paths = array_merge( array(APPPATH . 'third-party/kb/views/'=>TRUE), $this->_ci_view_paths);

  }
}