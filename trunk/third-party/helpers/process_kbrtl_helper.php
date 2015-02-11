<?php

class process_kbrtl {
  static $do_quit = FALSE;
  
  static $descriptorspec = array(
      0 => array("pipe", "r"),
      1 => array("pipe", "w"),
      2 => array("pipe", "w"),
  );
  static $processes = array();
  
  //2 => array("file", "/dev/null", "w"),
  static function start($process_path = NULL, $arg = NULL) {
    exec('rtl_433 -k 2>&1', $output);
    $dongle_count = (int)array_shift($output);
    print('COUNT:' . $dongle_count) . PHP_EOL;
    foreach($output as $dongle){
      $dongle_info = preg_split('`:`', $dongle, -1, PREG_SPLIT_NO_EMPTY);
      print $dongle_info[0] . '::' . $process_path . PHP_EOL;
    }
    
  }
  
}
