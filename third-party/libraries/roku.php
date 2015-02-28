<?php

class roku {

  static $timeout = 6;
  static $developer = '000000001fd544beffffffffb82c643e';
  static $info = array();
  static $ch = null;
  
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
      
      case 'cable_page_up':
        $url = '/keypress/Enter';
        self::post($url);
        break;
      
      case 'cable_page_down':
        $url = '/keypress/Backspace';
        self::post($url);
        break;
      
      
      
      case 'cable_guide':
        $url = '/keypress/Search';
        self::post($url);
        break;
      case 'cable_info':
        $url = '/keypress/Info';
        self::post($url);
        break;
      case 'cable_pause':
        $url = '/keypress/InstantReplay';
        self::post($url);
        break;
      case 'cable_play':
        $url = '/keypress/Play';
        self::post($url);
        break;
      case 'cable_rewind':
        $url = '/keypress/Rev';
        self::post($url);
        break;
      case 'cable_fast_forward':
        $url = '/keypress/Fwd';
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
    self::send_command($url);
    //exec("curl -d '' " . $curl, $dump); // & ?
  }
  
  
  static function check_curl() {
    if (is_null(self::$ch)) {
      self::$ch = curl_init();
      curl_setopt(self::$ch, CURLOPT_POST, 1);
      curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);
    }
  }

  static function send_command($str = NULL) {
    if (!empty($str)) {
      self::check_curl();
      $curl = 'http://' . kb::config('KB_ROKU_IP_PORT') . $str;
      curl_setopt(self::$ch, CURLOPT_URL, $curl);
      $server_output = curl_exec(self::$ch);
    }
  }
  
  
  
}