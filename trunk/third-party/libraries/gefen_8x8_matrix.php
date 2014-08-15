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
    self::debug('MESSAGE:' . $message);
    fwrite(self::$fp, $message);
    fclose(self::$fp);
  }

}
