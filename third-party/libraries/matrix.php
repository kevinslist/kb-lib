<?php

class matrix {

  static $matrix_ip;
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
    $t = time();
    self::$matrix_ip = kb::config('KB_MATRIX_IP');
    $c = file_get_contents("http://" . self::$matrix_ip . "/aj.shtml?_={$t}&a=getIndexData");
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
      self::$info['kb_output_state'] = array();
      foreach ($temp_state as $input) {
        self::$info['kb_output_state'][$i] = $input;
        self::$info['kb_output_state_by_name'][self::$info['outputs'][($i-1)]] = self::$info['inputs'][((int)$input-1)];
        $i++;
      }
    } else {
      itach::l('NO RESPONSE FROM GEFEN MATRIX GET STATUS');
    }

    return self::$info;
  }
  
  static function route($input, $output) {
    //self::init();
    //$message = "r {$input} {$output}\r";
    $t = time();
    $rest_url = 'http://' . self::$matrix_ip . "/aj.shtml?_={$t}&a=setMatrixChanges&i={$input}&o={$output}";
    //itach::l(print_r($rest_url, TRUE));
    $gc = file_get_contents($rest_url);
    self::init();
  }
  
  static function is_roku($zone){
    $is_roku = false;
    if('denon' == self::$info['kb_output_state_by_name'][$zone]){
      $is_roku = self::$info['kb_output_state_by_name']['denon'] == 'roku';
    }elseif('roku' == self::$info['kb_output_state_by_name'][$zone]){
      $is_roku = true;
    }
    return $is_roku;
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

}
