<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class kb_loader extends CI_Loader {
  public $kb_view_path = array();
  
  public function __construct() {
    parent::__construct();
    $this->kb_add_view_path(APPPATH . 'third_party/kb/templates/' . kb::$template_name . '/views/');
  }
  
  public function database($params = '', $return = FALSE, $active_record = NULL) {

    // Grab the super object
    $CI = & get_instance();
    // Do we even need to load the database class?
    if (class_exists('CI_DB') AND $return == FALSE AND $active_record == NULL AND isset($CI->db) AND is_object($CI->db)) {
      return FALSE;
    }
    require_once(BASEPATH . 'database/DB.php');
    // need this to parse config files
  
    $db = kb::db($params, $active_record);
  
    // Return DB instance
    if ($return === TRUE) {
      return $db;
    }
    // Initialize the db variable. Needed to prevent reference errors with some configurations
    $CI->db = '';
    $CI->db = & $db;
  }
  
  public function kb_set_view_path(){
    $temp = array();
    foreach($this->kb_view_path as $t){
      $temp[$t] = true;
    }
    $this->_ci_view_paths = array_merge(
          $this->_ci_view_paths,
          $temp
        );
  }
  
  public function kb_add_view_path($p = NULL){
    if(is_array($p)){
      $this->kb_view_path = array_merge($this->kb_view_path, $p);
    }else{
      $this->kb_view_path[] = $p;
    }
    $this->kb_set_view_path();
  }
}