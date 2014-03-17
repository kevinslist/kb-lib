<?php

if (!defined('BASEPATH')){  exit('No direct script access allowed'); }

class kb_controller extends CI_Controller {
  public $css_files;
  public $js_files;
  
  public function __construct($template_name = NULL) {
    if(empty($template_name)){
      die('NO TEMPLATE GIVEN');
    }
    kb::$template_name = $template_name;
    require_once dirname(__FILE__) . '/../templates/' . $template_name . '/kb_template.php';
    $this->css_files = kb_template_get_css();
    $this->js_files = kb_template_get_js();
    parent::__construct();
  }
  
  public function index() {
    $content = $this->home();
  }
  public function phpinfo(){
    print phpinfo();
    die();
  }
  public function error_404() {
    $this->index();
  }

  public function error_403() {
    $this->index();
  }
  
  public function render_page($c){
    $css = array_merge($this->css_files, kb::config('kb_css'));
    $css = kb::view('assets/css', array('css_files' => $css));
    $js = array_merge($this->js_files, kb::config('kb_js'));
    $js = kb::view('assets/js', array('js_files' => $js));
    print kb::view('layouts/default_layout', array('content'=>$c, 'css' => $css, 'js' => $js));
  }


}