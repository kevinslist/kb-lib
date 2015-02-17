<?php

class config_router {

  static $sem_key_special_info = '123323';
  static $key_config_router_special_info = 'config_router_special_info';
  
  static function route($signal) {
    // time is fine to send signal
    // only volume repeats past this point

    $remote = config_remote::get($signal);
    if ($remote) {

      if (!$signal['is-repeat']) {
        $remote['repeat'] = 0;
        $remote['previous-signal'] = $signal['remote-string'];
        $remote['last-sent'] = (int) $signal['last-signal'];
      } else {
        $remote['repeat'] ++;
      }

      // check for special signal
      $is_special = config_router::process_special($signal);

      if ($is_special) {
        
      } else {
        itach::send_signal($signal, $remote);
      }
    } else {
      print 'CONFIG_ROUTER NO REMOTE FOUND:' . PHP_EOL;
      print_r($signal);
      print PHP_EOL;
    }
  }
  
  static function execute_special_buffer($buffer){
    print 'execute_special_buffer' . PHP_EOL;
    print_r($buffer);
  }

  static function process_special($signal = null) {
    $is_special = false;
    // IS SPECIAL OR DURING SPECIAL TIMEFRAME
    try {
      $semaphore = sem_get(config_router::$sem_key_special_info, signal_controller::$sem_max, signal_controller::$sem_permissions, signal_controller::$sem_auto_release);
      //$this->log('Attempting to acquire semaphore_check_special');
      sem_acquire($semaphore);
      $special_info = kb::pval(config_router::$key_config_router_special_info);
      
      if(!is_array($special_info)){
        kb::pval(config_router::$key_config_router_special_info, array());
      }
      if(!is_null($signal)){
        $key = config_router::$key_config_router_special_info . $signal['header-string'];
        $start_signal = $is_special = config_remote::special($signal);
        
        if ($start_signal) {
          $special_info[$key] = array(
                        'start' => microtime(true),
                        'buffer' => array(),
                      );
          kb::pval(config_router::$key_config_router_special_info, $special_info);
        }else{
          if(isset($special_info[$key]['buffer'])){
            $old_time = $special_info[$key]['start'];
            $new_time = microtime(true);
            $diff = $new_time - $old_time;
            if($diff < 2){
              $is_special = true;
              $special_info[$key]['start']= $new_time;
              $special_info[$key]['buffer'][] = $signal['signal-name'];
            }
            //print('check special_diff:' . $diff . PHP_EOL);
            kb::pval(config_router::$key_config_router_special_info, $special_info);
          }
        }
      }else{
        // called from cron to check if anything to process
        $is_special = true;
        $new_time = microtime(true);
        $add_back = array();
        
        foreach($special_info as $remote_id => $remote){
          $old_time = $remote['start'];
          $diff = $new_time - $old_time;
          if($diff > 1){
            if(count($special_info[$remote_id]['buffer'])){
              //print 'PROCESS SPECIAL BUFFER:' . $remote_id . PHP_EOL;
              config_router::execute_special_buffer($special_info[$remote_id]['buffer']);
            }
          }else{
            print 'SPECIAL DIFF NOT GREAT ENOUGH:' . $diff . PHP_EOL;
            $add_back[$remote_id] = $remote;
          }
        }
        kb::pval(config_router::$key_config_router_special_info, $add_back);
      }
    } catch (Exception $ex) {
      $this->log('EXCEPTION IN process_special:');
      $this->log(ex);
    } finally {
      sem_release($semaphore);
    }
    return $is_special;
  }

  static function add_new_special_signal($signal = null) {
    print "CHECK config_remote::add_new_signal:" . PHP_EOL;
  }

  static function route_special() {
    //print 'ROUTE SPECIAL CHECK' . PHP_EOL;
    config_router::process_special();
  }

}
