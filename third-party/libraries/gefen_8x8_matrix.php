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
  static $matrix_key_info = 'kb_gefen_matrix_info';

  static function init() {
    if (!self::$inited) {
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
    $matrix_ip = kb::config('KB_MATRIX_IP');
    $rest_url = 'http://' . $matrix_ip . "/aj.shtml?_={$t}&a=setMatrixChanges&i={$input}&o={$output}";
    //itach::l(print_r($rest_url, TRUE));
    $gc = file_get_contents($rest_url);
   // itach::l(print_r($gc, TRUE));
    gefen_8x8_matrix::get_status(TRUE);
    //itach::l(print_r(self::$state, TRUE));
  }

  static function set_input_for_zone($zone, $input) {
    //$output_index = (int)itach::$remote_zones[$zone];
    $output_index = self::$info['kb_outputs'][$zone];
    $input_index = self::$info['kb_inputs'][$input];
    self::route($input_index, $output_index);
  }

  static function send_pulse_command() {
    //#hpd_pulse Command
    //http://www.gefen.com/pdf/GTB-HDFST-848.pdf
  }

  static function get_status($override = false) {
    if ($override || empty(gefen_8x8_matrix::$info)) {
      self::$info = kb::pval(self::$matrix_key_info);

      if ($override || empty(self::$info)) {
        $t = time();
        $c = file_get_contents("http://192.168.1.72/aj.shtml?_={$t}&a=getIndexData");
        if (!empty($c)) {
          self::$info = json_decode($c, TRUE);
          $outputs = array_flip(self::$info['outputs']);
          $inputs = array_flip(self::$info['inputs']);

          self::$info['kb_inputs'] = array();
          self::$info['kb_outputs'] = array();

          foreach ($outputs as $k => $v) {
            self::$info['kb_outputs'][$k] = (int) $v + 1;
          }
          foreach ($inputs as $k => $v) {
            self::$info['kb_inputs'][$k] = (int) $v + 1;
          }
          $temp_state = explode(',', self::$info['sstr']);
          $i = 1;
          self::$info['kb_state'] = array();
          foreach ($temp_state as $input) {
            self::$info['kb_state'][$i] = $input;
            $i++;
          }
          kb::pval(self::$matrix_key_info, self::$info);
        }else{
          itach::l('NO RESPONSE FROM GEFEN MATRIX GET STATUS');
        }
      } else {
        //itach::l('MATRIX INFO FROM MEMCACHE');
      }
    }
    
    return self::$info;
  }

}
