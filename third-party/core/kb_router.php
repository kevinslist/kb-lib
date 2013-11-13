<?php

spl_autoload_register('kb_router::autoload');

class kb_router extends CI_Router {

  private $_suffix = "_controller";
  var $error_controller = 'default_controller';
  var $error_method_404 = 'error_404';
  
  public function __construct() {
    parent::__construct();
  }
  
  function _validate_request($segments) {
    // Retain the original segments
    $original_segments = array_slice($segments, 0);
    // Add suffix to the end
    $segments[0] = strtolower($segments[0] . $this->_suffix);

    if (file_exists(APPPATH . 'controllers/' . $segments[0] . EXT)) {
      return $segments;
    }
    // OK, revert to the original segment
    $segments[0] = $original_segments[0];

    // Is the controller in a sub-folder?
    if (is_dir(APPPATH . 'controllers/' . $segments[0])) {
      // Set the directory and remove it from the segment array
      $this->set_directory($segments[0]);
      $segments = array_slice($segments, 1);

      if (count($segments) > 0) {
        $segments[0] = strtolower($segments[0] . $this->_suffix);

        // Does the requested controller exist in the sub-folder?
        if (!file_exists(APPPATH . 'controllers/' . $this->fetch_directory() . $segments[0] . EXT)) {
          error_404($this->fetch_directory() . $segments[0]);
        }
      } else {
        // Add suffix to the end
        $this->default_controller = strtolower($this->default_controller . $this->_suffix);
        $this->set_class($this->default_controller);
        $this->set_method('index');
        // Does the default controller exist in the sub-folder?
        if (!file_exists(APPPATH . 'controllers/' . $this->fetch_directory() . $this->default_controller . EXT)) {
          $this->directory = '';
          return array();
        }
      }
      return $segments;
    }

    // Can't find the requested controller...
    return $this->error_404();
  }

  function error_404() {
    $this->directory = "";
    $segments = array();
    $segments[] = $this->error_controller;
    $segments[] = $this->error_method_404;
    return $segments;
  }

  function fetch_class() {
    $this->check_method();
    return $this->class;
  }

  function check_method() {
    $ignore_remap = true;
    $class = $this->class;
    if (class_exists($class)) {
      // methods for this class
      $class_methods = array_map('strtolower', get_class_methods($class));
      // ignore controllers using _remap()
      if ($ignore_remap && in_array('_remap', $class_methods)) {
        return;
      }
      if (!in_array(strtolower($this->method), $class_methods)) {
        $this->directory = "";
        $this->class = $this->error_controller;
        $this->method = $this->error_method_404;
        include(APPPATH . 'controllers/' . $this->fetch_directory() . $this->error_controller . EXT);
      }
    }
  }

  function fetch_page_id() {
    $classname = str_replace('_controller', '', $this->fetch_class());
    $method = $this->fetch_method();
    $method = $method == 'index' ? '' : '/' . $method;
    return $classname . $method;
  }

  function show_404() {
    include(APPPATH . 'controllers/' . $this->fetch_directory() . $this->error_controller . EXT);
    call_user_func(array($this->error_controller, $this->error_method_404));
  }
  
  public static function autoload($class) {
    $found = false;
    $paths = array(
        'models' => strtolower(dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $class . '.php'),
    );
    foreach ($paths as $k => $path) {
      if (is_readable($path)) {
        require_once($path);
        $found = true;
        break;
      }
    }
    return $found;
  }


}