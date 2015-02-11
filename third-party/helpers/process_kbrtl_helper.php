<?php

/*
 * itach api id: 057d6b19-5f2c-4deb-bd7c-31f659caaf4e
 * for web interface: https://irdatabase.globalcache.com
 * https://irdatabase.globalcache.com/api/v1/057d6b19-5f2c-4deb-bd7c-31f659caaf4e/manufacturers
 */

class process_kbrtl {

  static $do_quit = FALSE;
  

  //2 => array("file", "/dev/null", "w"),
  static function start($app_directory = NULL, $arg = NULL) {
    $devices = exec('rtl_433 -t');
    print_r($devices);
    
  }
}
