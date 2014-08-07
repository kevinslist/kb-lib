<?php

class gefen_8x8_matrix {

  static $ip = '192.168.1.72';
  static $udp = '192.168.1.72';
  static $udp_port = '23';

  static function init() {

    $udp_url = 'tcp://' . self::$udp . ':' . self::$udp_port;
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

}
