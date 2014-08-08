<?php

class gefen_8x8_matrix {

  static $udp_url = 'tcp://192.168.1.72:23';
  static $socket = NULL;
  
  static function init() {
    print $udp_url . '<br />';
    $fp = stream_socket_client($udp_url, $errno, $errstr);
    if (!$fp) {
      echo "ERROR: $errno - $errstr<br />\n";
    } else {
      fwrite($fp, "r 7 D\r");
      fwrite($fp, "r 4 A\r");
      fclose($fp);
    }
  }
  
  
  static function r($commands = array()) {
    
    
  }

}
