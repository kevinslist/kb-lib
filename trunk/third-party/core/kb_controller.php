<?php

if (!defined('BASEPATH')) {
  exit('No direct script access allowed');
}

class kb_controller extends CI_Controller {
  public $css_files;
  public $js_files;
  public $force_login = FALSE;
  public $page_title = array();
  
  public function __construct($template_name = NULL) {
    spl_autoload_register('kb_controller::autoload');
    if (empty($template_name)) {
      die('NO TEMPLATE GIVEN');
    }
    kb::$template_name = $template_name;
    require_once dirname(__FILE__) . '/../templates/' . $template_name . '/kb_template.php';
    $this->ajax_call = isset($_SERVER['HTTP_X_REQUESTED_WITH']);
    parent::__construct();
  }

  public function index() {
    $content = $this->home();
  }

  public function phpinfo() {
    print phpinfo();
    die();
  }

  public function error_404() {
    $this->index();
  }

  public function error_403() {
    $this->index();
  }
  
  public function page_title($set_value = NULL){
    $return = $this;
    if(is_null($set_value)){
      $return = implode(' | ', $this->page_title);
    }else{
      $this->page_title[] = $set_value;
    }
    return $return;
  }

  public function render_page($content = '') {
    $this->css_files = kb_template_get_css();
    $this->js_files = kb_template_get_js();
    $css = array_merge($this->css_files, kb::config('kb_css'));
    $css = kb::view('assets/css', array('css_files' => $css));
    $js = array_merge($this->js_files, kb::config('kb_js'));
    $js = kb::view('assets/js', array('js_files' => $js));
    print kb::view('layouts/default_layout', array('content' => $content, 'css' => $css, 'js' => $js));
  }

  function _remap($method = NULL, $params = NULL) {
    $uri_parts = explode('/', uri_string());
    $kb_func     = str_replace('-', '_', current($uri_parts));
    if ($method == 'error_404' && !empty($kb_func) && method_exists($this, $kb_func)) {
      $this->$kb_func();
    } elseif ($method != 'error_404' && method_exists($this, $method)) {
      call_user_func_array(array($this, $method), $params);
    } else {
      if (!empty($kb_func)) {
        $this->client->add_message('404: ' . $kb_func, 'error');
      }
      $this->index($kb_func);
    }
  }
  public static function autoload($class) {
    $found = false;
    $paths = array(
      'core' => strtolower(dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . $class . '.php'),
    );
    foreach ($paths as $path) {
      if (is_readable($path)) {
        require_once($path);
        $found = true;
        break;
      }
    }
    return $found;
  }
  

}
/*
 * 
  
 */