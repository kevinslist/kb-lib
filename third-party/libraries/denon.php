<?php

class denon {

  static $denon_ip = '192.168.1.129';
  static $denon_put_url = 'http://192.168.1.129/MainZone/index.put.asp';
  static $status = array();
  static $denon_input_index = 4;
  static $denon_output_index = 4;
  static $power_on = FALSE;
  static $volume_level = -40.0;
  static $last_volume_sent = 0;
  static $ch = NULL;
  static $volume_start_repeat = 2;
  static $volume_current_repeat = 2;
  static $kb_denon_key_info = 'kb_denon_info';

  // set volume: PutMasterVolumeSet/-10.0
  // volume up: PutMasterVolumeBtn/>
  // volume down: PutMasterVolumeBtn/<
  // mute: PutVolumeMute/on
  //
  // put on iradio: PutZone_InputFunction/IRADIO
  // put on sat/cab: PutZone_InputFunction/SAT/CBL

  static function status($force = false) {
    $info = kb::pval(denon::$kb_denon_key_info);
    if(!is_array($info) || $force){
      self::check_curl();
      $t = time();
      $s = file_get_contents('http://' . kb::config('KB_DENON_IP') . '/goform/formMainZone_MainZoneXml.xml?_=' . $t);
      $info = array();
      preg_match_all('`<([^>]+)><value>([^<]+)</value>`', $s, $matches);

      foreach ($matches[1] as $k => $setting_name) {
        $info[$setting_name] = $matches[2][$k];
      }
      $info['power'] = (self::$status['ZonePower'] == 'ON');
      $info['volume_level'] = (float) self::$status['MasterVolume'];
      kb::pval(denon::$kb_denon_key_info, $info);
    }
    
    itach::l(print_r($info, true));
  }
  
  static function volume_up(){
    $info = denon::status();
    $signal = 'PutMasterVolumeBtn/>';
    self::set_volume_repeat();
    for($i = 0; $i < $info['volume_current_repeat']; $i++){
      self::send_command($signal);
    }
  }
  
  static function volume_down(){
    $info = denon::status();
    $signal = 'PutMasterVolumeBtn/<';
    self::set_volume_repeat();
    for($i = 0; $i < $info['volume_current_repeat']; $i++){
      self::send_command($signal);
    }
  }
  
  static function set_volume_repeat(){
    $info = denon::status();
    $t = time();
    $d = $t - self::$last_volume_sent;
    if($d < 2){
      self::$volume_current_repeat = self::$volume_current_repeat = (self::$volume_current_repeat + 2);
    }else{
      self::$volume_current_repeat = self::$volume_start_repeat;
    }
    itach::l('self::$volume_current_repeat:' . self::$volume_current_repeat);
    self::$last_volume_sent = $t;
  }
  
  static function set_sat_cbl(){
    $signal = 'PutZone_InputFunction/SAT/CBL';
    self::send_command($signal);
  }
  
  static function toggle_power($on = NULL){
    self::status();
    if(is_null($on)){
      if(self::$power_on){
        $signal = 'PutZone_OnOff/OFF';
      }else{
        $signal = 'PutZone_OnOff/ON';
      }
    }else{
      $signal = $on ? 'PutZone_OnOff/ON' : 'PutZone_OnOff/OFF';
    }
    self::send_command($signal);
  }
  
  static function check_curl(){
    if (empty(self::$ch)) {
      self::$ch = curl_init();
      curl_setopt(self::$ch, CURLOPT_URL, self::$denon_put_url);
      curl_setopt(self::$ch, CURLOPT_POST, 1);
      curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, true);

    }
  }

  static function send_command($str = NULL) {
    if (!empty($str)) {
      self::check_curl();
      $post_str = http_build_query(array('cmd0' => $str));
      itach::l('DENONS POST STRING:' . $post_str);
      curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $post_str);
      $server_output = curl_exec (self::$ch);
      usleep(30000);
    }
  }

}
