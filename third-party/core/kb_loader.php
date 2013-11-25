<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class kb_loader extends CI_Loader {
  public function __construct() {
    parent::__construct();
    $this->_ci_view_paths = array_merge( 
                                        $this->_ci_view_paths,
                                        array(
                                              APPPATH . 'third_party/kb/forms/v1/views/'=>TRUE,
                                              APPPATH . 'third_party/kb/views/'=>TRUE
                                            ) 
                                      );

  }
}