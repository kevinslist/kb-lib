<?php

class config_remote {
  
  static function get($signal = null, $reset = false){
    return isset(self::$remote_map[$signal['remote_command_remote_id']]) ? self::$remote_map[$signal['remote_command_remote_id']] : false;
  }
  
  static function set($remote_id = null, $zone = null){
    self::$remote_map[$remote_id]['zone'] = $zone;
  }
  
  static function special($signal){
    // print 'CHECKING.. SPECIAL:' . $signal['signal-name'] . PHP_EOL;
    $is_special = in_array($signal['remote_command_signal_name'], config_remote::$remote_special_codes);
    return $is_special;
  }
  
  static $remote_special_codes = array(
    'cable_help'
  );
  
  
  //$default_remote_info
  static $remote_map = array(
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
