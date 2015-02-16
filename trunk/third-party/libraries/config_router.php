<?php

class config_router {
  
  static function route($signal){
    //print 'ROUTE NEW REMOTE SIGNAL: ' . PHP_EOL;
    //print_r($signal);
    //print PHP_EOL;
    
    $current_time = (int) $signal['last-signal'];
    $remote_code = '#' . $signal['header-string'];
    $current_signal = $signal['remote-string'];

    if (!$signal['is-repeat']) {
      itach::$remotes[$remote_code]['repeat'] = 0;
      itach::$remotes[$remote_code]['previous-signal'] = $current_signal;
      itach::$remotes[$remote_code]['last-sent'] = $current_time;
    }
    itach::send_signal($signal);
    
    
  }
  
  static function route_special(){
   print 'ROUTE SPECIAL CHECK' . PHP_EOL;
  
  }

}
