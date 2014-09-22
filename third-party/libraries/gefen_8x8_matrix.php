<?php

class gefen_8x8_matrix {

  static $matrix_ip = '192.168.1.72';
  static $udp_url = 'tcp://192.168.1.72:23';
  static $socket = NULL;
  static $timer = 0;
  static $timer_total = 0;
  static $inited = FALSE;
  static $fp = NULL;
  static $info = array();
  static $outputs = array();
  static $inputs = array();
  static $state = array();
  
  static function init() {
    if(!self::$inited){
      self::$inited = TRUE;
      self::debug(self::$udp_url);
      self::$fp = stream_socket_client(self::$udp_url, $errno, $errstr, 1);
      if (!self::$fp) {
        echo "ERROR: $errno - $errstr<br />\n";
      } else {
        $t = fread(self::$fp, 8192);
      }
    }
  }

  static function gt() {
    $c = microtime(true);
    self::$timer = empty(self::$timer) ? $c : self::$timer;
    $r = $c - self::$timer;
    self::$timer = $c;
    self::$timer_total += $r;
    return $r;
  }

  static function route($input, $output) {
    //self::init();
    //$message = "r {$input} {$output}\r";
    $t = time();
    $rest_url = 'http://' . self::$matrix_ip . "/aj.shtml?_={$t}&a=setMatrixChanges&i={$input}&o={$output}";
    itach::l(print_r($rest_url, TRUE));
    $gc = file_get_contents($rest_url);
    itach::l(print_r($gc, TRUE));
    self::get_status();
    itach::l(print_r(self::$state, TRUE));
  }
  
  static function set_input_for_zone($zone, $input){
    //$output_index = (int)itach::$remote_zones[$zone];
    $output_index = self::$outputs[$zone];
    $input_index = self::$inputs[$input];
    self::route($input_index, $output_index);
  }
  
  static function send_pulse_command(){
    //#hpd_pulse Command
    //http://www.gefen.com/pdf/GTB-HDFST-848.pdf
  }
  
  static function get_status(){
    $t = time();
    $c =  file_get_contents("http://192.168.1.72/aj.shtml?_={$t}&a=getIndexData");
    if(!empty($c)){
      self::$info = json_decode($c, TRUE);
    }
    $outputs = array_flip(self::$info['outputs']);
    $inputs = array_flip(self::$info['inputs']);
    if(empty(self::$outputs)){
      foreach($outputs as $k => $v){
        self::$outputs[$k] = (int)$v + 1;
      }
      foreach($inputs as $k => $v){
        self::$inputs[$k] = (int)$v + 1;
      }
    }
    $temp_state = explode(',', self::$info['sstr']);
    $i = 1;
    foreach($temp_state as $input){
      self::$state[$i] = $input;
      $i++;
    }
    return self::$info;
  }

}
