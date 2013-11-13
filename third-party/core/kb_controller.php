<?php

if (!defined('BASEPATH')){  exit('No direct script access allowed'); }

class kb_controller extends CI_Controller {

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