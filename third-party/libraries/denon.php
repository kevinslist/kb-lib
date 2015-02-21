<?php

class denon {

  static $denon_ip = '192.168.1.129';
  static $denon_put_url = 'http://192.168.1.129/MainZone/index.put.asp';
  static $info = array();
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

  static function init() {
    $t = time();
    $s = file_get_contents('http://' . kb::config('KB_DENON_IP') . '/goform/formMainZone_MainZoneXml.xml?_=' . $t);
    preg_match_all('`<([^>]+)><value>([^<]+)</value>`', $s, $matches);

    foreach ($matches[1] as $k => $setting_name) {
      self::$info[$setting_name] = $matches[2][$k];
    }
    self::$info['power'] = (self::$info['ZonePower'] == 'ON');
    self::$info['volume_level'] = (float) self::$info['MasterVolume'];
    self::$info['volume_current_repeat'] = 5;

    return self::$info;
  }

  static function volume_up() {
    $signal = 'PutMasterVolumeBtn/>';
    for ($i = 0; $i < 5; $i++) {
      self::send_command($signal);
    }
  }

  static function volume_down() {
    $signal = 'PutMasterVolumeBtn/<';
    for ($i = 0; $i < 5; $i++) {
      self::send_command($signal);
    }
  }

  static function set_sat_cbl() {
    $signal = 'PutZone_InputFunction/SAT/CBL';
    self::send_command($signal);
  }

  static function toggle_power($on = NULL) {
    if (is_null($on)) {
      if (self::$info['power']) {
        $signal = 'PutZone_OnOff/OFF';
      } else {
        $signal = 'PutZone_OnOff/ON';
      }
    } else {
      $signal = $on ? 'PutZone_OnOff/ON' : 'PutZone_OnOff/OFF';
    }
    self::send_command($signal);
  }

  static function check_curl() {
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
      //itach::l('DENONS POST STRING:' . $post_str);
      curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $post_str);
      $server_output = curl_exec(self::$ch);
      usleep(15000);
      denon::init();
    }
  }

}
