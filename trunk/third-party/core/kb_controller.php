<?php

if (!defined('BASEPATH')){  exit('No direct script access allowed'); }

spl_autoload_register('kb::autoload');
class kb_controller extends CI_Controller {
  static $template			= 'nasa1';
  static $templates_dir	= 'templates';
  //Page info
  public $page_id    = NULL;
  protected $view       = false;
  protected $layout     = 'default';
  protected $navigation = array();
  //Page contents
  public $js          = array();
  public $css         = array();
  public $GFont       = array();
  public $content     = false;
  //Page Meta
  public $title       = array();
  public $description = array();
  public $body_classes= array();
  // Models
  public $client      = null;
  public $mobile      = FALSE;
  public $bad_ie       = FALSE;
  public $is_ie       = FALSE;

  public $sidebar_blocks = array();
  public $footer_blocks = array();
  /**
   * @desc build and setup basic info
   */
  public function __construct($template_name = 'nasa1', $mobile_template = NULL) {
    parent::__construct();
    $this->template($template_name);
    bsg_template::init();
    
    $this->load->library('user_agent');
    if($this->agent->is_mobile() && !empty($mobile_template)){
      //print $this->agent->mobile();
      //$template_name = $mobile_template;
    }
    $this->agent->ie = FALSE;

    if($this->agent->browser() == 'Internet Explorer'){
      $this->is_ie = TRUE;
      if(preg_match('`Trident/4\.0`i', $this->agent->agent)){
        $this->agent->ie = 8;
      }elseif(preg_match('`Trident/5\.0`i', $this->agent->agent)){
        $this->agent->ie = 9;
      }elseif(preg_match('`Trident/6\.0`i', $this->agent->agent)){
        $this->agent->ie = 10;
      }else{
        $this->agent->ie = 7;
      }
      $this->bad_ie = $this->agent->ie < 9;
    }
    $this->body_classes[] = 'bsg';
    $this->body_classes[] = 'no-js';
    $this->body_classes[] = ENVIRONMENT;
    if($this->agent->ie){
      $this->body_classes[] = 'ie' . $this->agent->ie;
    }

    if(!isset(bsg_template::$static_css_js) || !bsg_template::$static_css_js){
      $this->add_css(bsg_template::get_css($this->agent));
      $this->add_js(bsg_template::get_js($this->agent));
    }
		$this->page_id = $this->router->fetch_page_id();
    $this->layout =  isset(bsg_template::$default_layout) ? bsg_template::$default_layout : $this->layout;

    if($this->client->nams_id_required){
      $test = $this->client->can('bsg-auth-access-application');
      if(!$test){
        $this->_no_access();
      }
    }
  }


  /**
   * @desc to be overwritten in My_Controller if needed
   */
  public function render_page_called(){

  }

  /**
   * @desc render the final page composed on template and page content
   */
  public function render_page($content = NULL, $do_echo = TRUE) {
    $this->render_page_called();
    if(isset(bsg_template::$renders_pages) && bsg_template::$renders_pages){
      $page_string = bsg_template::render_page($content, $this);
    }else{
      $vars_page = array();
      $vars_page['client']      = $this->client;
      $nav_items                = $this->client->logged_in() ? $this->client->get_navigation() : (isset($this->client->public_links) ? $this->client->get_public_navigation() : array());
      $vars_page['navigation']  = (!empty($nav_items )) ? bsg_menu($nav_items, $this, $this->get_page_id()) : false;

      $top_items								= $this->client->get_top_navigation();
      $vars_page['top_items']		= (!empty($top_items)) ? bsg_menu($top_items, $this, $this->client->name) : '';

      $vars_page['login_url'] = !empty($this->client->login_url) ?  $this->client->login_url : null;
      $vars_page['misc'] = !empty($this->client->misc) ?  $this->client->misc : null;

      $vars_page['nams_restricted'] = FALSE;
      $vars_page['content']         = $content;
      $vars_page['title']           = $this->get_title();
      $vars_page['description']     = $this->get_description();
      $vars_page['template']				= $this->template();
      $vars_page['user_messages']		= $this->client->get_messages();
      $vars_page['bsg_html_tag']		= bsg::get_client_html_tag();
      $vars_page										= array_merge($vars_page, bsg_template::get_content($vars_page));

      $this->add_css('template-final.css');
      $vars_page['js']          = $this->js;
      $vars_page['css']         = $this->css;
      $vars_page['body_classes'] = implode(' ', $this->body_classes);

      $page_string = $this->load->view($this->layout(), $vars_page);
    }
    if($do_echo){
      echo $page_string;
    }else{
      return $page_string;
    }
  }

  public function _no_access(){
    $autorized = $this->client->autorized();
    $vars_page = array();
    $vars_page['client']      = $this->client;
    $nav_items                = array();
    $vars_page['navigation']  = false;
		$top_items								= $this->client->get_top_navigation();
		$vars_page['top_items']		= (!empty($top_items)) ? bsg_menu($top_items, $this, $this->client->name) : '';


    $vars_page['nams_restricted'] = TRUE;
    $vars_page['title']           = $this->get_title();
    $vars_page['description']     = $this->get_description();
    $vars_page['template']				= $this->template();
		$vars_page['user_messages']		= $this->client->get_messages();
		$vars_page['bsg_html_tag']		= bsg::get_client_html_tag();
		$vars_page										= array_merge($vars_page, bsg_template::get_content($vars_page));
    $vars_page['content']         = $this->build_content('common/no_access.php', $vars_page, TRUE);

    $this->add_css('template-final.css');
    $vars_page['js']          = $this->js;
    $vars_page['css']         = $this->css;
    //"cannot access application: NAMS/IdMax account ID not found."
		die($this->load->view($this->layout(), $vars_page, TRUE));
  }


  public function get_page_id() {
    return $this->page_id;
  }


  public function template($template_name = null){
    $ret = $this;
    if( is_null($template_name)){
      $ret = self::$template;
    }else{
      bsg_controller::$template = $template_name;
    }
    return $ret;
  }

  public function layout(){
    $test = dirname($_SERVER['SCRIPT_FILENAME']) . '/application/views/layouts/' . $this->layout . '.php';
    
    if(file_exists($test)){
      $layout_load = 'layouts/' . $this->layout;
    }else{
      $layout_load = $layout_path = self::$templates_dir . '/' . self::$template . '/' . $this->layout;
    }
    return $layout_load;
  }

  /**
   * @desc Create content for the current page
   */
  public function build_content($view_name, $data='', $template=false) {
    $content = '';
    if(!$template){
      $content = $this->load->view($view_name, $data, true);
    }else{
      $content = $this->load->view(self::$templates_dir . '/' . self::$template . '/' . $view_name, $data, true);
    }
    return $content;
  }

  /**
   * @desc get function for template
   */
  public function get_template() {
    return $this->template;
  }

  public function add_body_class($class){
    $this->body_classes[$class] = $class;
  }

  /**
   * @desc add CSS file(s) to template via string or array
   */
  public function add_css($css = null) {
    if (is_array($css)) {
      foreach ($css as $c) {
        $this->css[$c] = $c;
      }
    } elseif (!empty($css)) {
      $this->css[$css] = $css;
    }
  }


  /**
   * @desc add JS file(s) to template via string or array
   */
  public function add_js($js = null) {

    if (is_array($js)) {
      foreach ($js as $j) {
        $this->js[$j] = $j;
      }
    } elseif (!empty($js)) {
      $this->js[$js] = $js;
    }
  }

  /**
   * @desc add Title(s)to template via string or array
   */
  public function add_title($title = null) {
    if (is_array($title)) {
      foreach ($title as $t) {
        array_unshift($this->title, $t);
      }
    } elseif (!empty($title)) {
      array_unshift($this->title, $title);
    }
  }

  /**
   * @desc set Title(s)to template via string or array
   */
  public function set_title($title = null) {
    $this->title = array();
    $this->add_title($title);
  }

  /**
   * @desc get Title
   */
  public function get_title() {
    return implode(' | ', $this->title);
  }

  /**
   * @desc add description(s)to template via string or array
   */
  public function add_description($description = null) {
    if (is_array($description)) {
      foreach ($description as $d) {
        $this->description[] = $d;
      }
    } elseif (!empty($description)) {
      $this->description[] = $description;
    }
  }

  /**
   * @desc set Title(s)to template via string or array
   */
  public function set_description($description = null) {
    $this->title = array();
    $this->add_description($description);
  }

  /**
   * @desc get Title
   */
  public function get_description() {
    return implode(', ', $this->description);
  }

  public static function autoload($class) {
    $found = false;
    $dir = dirname(__FILE__) . '/../views/' . self::$templates_dir . '/' . self::$template;
    $check_path = strtolower($dir . DIRECTORY_SEPARATOR . $class . '.php');

    if (is_readable($check_path)) {
      require_once($check_path);
      $found = true;
    }
    return $found;
  }


}