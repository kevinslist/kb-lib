<?php

class gefen_8x8_matrix {

  static $udp_url = 'tcp://192.168.1.72:23';
  static $socket = NULL;
  static $timer = 0;
  static $timer_total = 0;
  static $debugging_level = TRUE;
  static $inited = FALSE;
  static $fp = NULL;

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

  static function debug($message) {
    if (self::$debugging_level) {
      echo $message . '<br />';
    }
  }

  static function route($input, $output) {
    self::init();
    $message = "r {$input} {$output}\r";
    $t = time();
    $gc = file_get_contents("http://192.168.1.72/aj.shtml?_={$t}&a=setMatrixChanges&i=7&o=1");
    //http://192.168.1.129/goform/formMainZone_MainZoneXml.xml
    self::debug('MESSAGE:' . $message);
    //fwrite(self::$fp, $message);
    fclose(self::$fp);
  }
  
  static function set_input_for_zone($zone, $input){
    $output_index = (int)itach::$remote_zones[$zone];
    print 'GEFEN S-I-F-Z:' . $output_index . '::' . $input . PHP_EOL;
    print_r(itach::$info);
  }
  
  static function send_pulse_command(){
    //#hpd_pulse Command
    
  }
  
  static function get_status(){
    $t = time();
    $c =  file_get_contents("http://192.168.1.72/aj.shtml?_={$t}&a=getIndexData");
    if(!empty($c)){
      $r = json_decode($c, TRUE);
    }
    return $r;
  }

}
