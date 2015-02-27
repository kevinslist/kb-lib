<?php

class roku {

  static $timeout = 6;
  static $developer = '000000001fd544beffffffffb82c643e';
  static $info = array();

  static function init() {
    $init_url = '/query/apps';
    self::$info = hue::get($init_url);
  }
  
  static function route($signal = null, $remote = null){
    $is_roku = true;
    $signal_name = $signal['remote_command_signal_name'];
    switch($signal_name){
      case 'cable_menu':
        $url = '/keypress/home';
        self::post($url);
        break;
      case 'cable_ok_select':
        $url = '/keypress/Select';
        self::post($url);
        break;
      case 'cable_last':
        $url = '/keypress/Back';
        self::post($url);
        break;
      case 'cable_left_arrow':
        $url = '/keypress/Left';
        self::post($url);
        break;
      case 'cable_right_arrow':
        $url = '/keypress/Right';
        self::post($url);
        break;
      case 'cable_down_arrow':
        $url = '/keypress/Down';
        self::post($url);
        break;
      case 'cable_up_arrow':
        $url = '/keypress/Up';
        self::post($url);
        break;
      default:
        $is_roku = false;
        print 'ROKU NOT KNOWN SIGNAL- PASS THRU:' . $signal_name . PHP_EOL;
        break;
    }
    
    return $is_roku;
  }
  
  static function post($url){
    $curl = 'http://' . kb::config('KB_ROKU_IP_PORT') . $url;
    exec("curl -d '' " . $curl); // & ?
  }
}