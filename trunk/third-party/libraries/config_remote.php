<?php

class config_remote {
  static $remote_map = array();
  
  static function get($signal = null, $reset = false){
   
    $remote_id = '#' . $signal['header-string'];
    
    //print 'config_remote::get(' . $remote_id . ')' . PHP_EOL;
    config_remote::$remote_map = kb::pval('kb-remote-map');
    if(!is_array(config_remote::$remote_map) || empty(config_remote::$remote_map)){
      config_remote::$remote_map = config_remote::$remote_map_default;
      kb::pval('kb-remote-map', config_remote::$remote_map);
    }
    return isset(config_remote::$remote_map[$remote_id]) ? config_remote::$remote_map[$remote_id] : false;
  }
  
  static function set($remote_info, $remote_string){
    $remote_id = '#' . $remote_string;
    config_remote::$remote_map[$remote_id] = $remote_info;
    kb::pval('kb-remote-map', config_remote::$remote_map);
  }
  
  static function special($signal){
    // print 'CHECKING.. SPECIAL:' . $signal['signal-name'] . PHP_EOL;
    $is_special = in_array($signal['signal-name'], config_remote::$remote_special_codes);
    return $is_special;
  }
  
  static $remote_special_codes = array(
    'cable_help'
  );
  
  
  //$default_remote_info
  static $remote_map_default = array(
    '#0110101001' => array(
      'zone' => '80inch',
      'special-counter' => 100,
      'special-buffer' => array(),
      'repeat' => 0,
      'previous-signal' => '',
      'last-sent' => '',
    ),
    '#0111000011' => array(
      'zone' => '80inch',
      'special-counter' => 100,
      'special-buffer' => array(),
      'repeat' => 0,
      'previous-signal' => '',
      'last-sent' => '',
    ),
    '#0110000101' => array(
      'zone' => 'bedroom',
      'special-counter' => 100,
      'special-buffer' => array(),
      'repeat' => 0,
      'previous-signal' => '',
      'last-sent' => '',
    ),
    '#0110010111' => array(
      'zone' => 'workout',
      'special-counter' => 100,
      'special-buffer' => array(),
      'repeat' => 0,
      'previous-signal' => '',
      'last-sent' => '',
    ),
  );

}
