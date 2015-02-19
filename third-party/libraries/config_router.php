<?php

class config_router {

  static $key_config_router_signal_queue = 'config_router_signal_queue';
  static $semaphore = null;
  static $semaphore_last_unlocked = 0;
  static $current_signal_queue = null;
 
   static function process_signal(){
    try {
      $key = kb::config('KB_CONFIG_ROUTER_INFO_SEM_LOCK_PORT');
      self::$semaphore = sem_get($key);
      $locked = sem_acquire(self::$semaphore);
      if ($locked) {
        $signal_queue_key = kb::config('KB_SIGNAL_QUEUE_KEY');
        self::$current_signal_queue = kb::mval($signal_queue_key);
        kb::mval($signal_queue_key, array());
        print 'MVAL(' . $signal_queue_key . '):' . PHP_EOL;
        print_r(self::$current_signal_queue);
        print PHP_EOL;
      }
    } catch (Exception $ex) {
      
    } finally {
      if (!empty(self::$semaphore)) {
        sem_release(self::$semaphore);
      }
    }
   }
  
  static function save(){
    return kb::pval(self::$key_config_router_signal_queue, self::$info);
  }
  
  static function init(){
    self::$info = kb::pval(self::$key_config_router_special_info);
    if(!is_array(self::$info) || empty(self::$info)){
      self::$info = self::$info_defaults;
    }
  }
  
  static function unlock(){
    if(!empty(self::$semaphore)){
      sem_release(self::$semaphore);
    }
  }
  
  static function lock(){
    if(time() - self::$semaphore_last_unlocked > 3){
      self::unlock();
    }
    $key = kb::config('KB_CONFIG_ROUTER_INFO_SEM_LOCK_PORT');
    self::$semaphore = sem_get($key);
    return sem_acquire(self::$semaphore);
  }
  static function basic_lock_template($signal){
    try{
      self::lock();
      
      
    } catch (Exception $ex) {

    }  finally {
      self::unlock();
    }
  }

  static function route($signal) {
    // time is fine to send signal
    // only volume repeats past this point

    $remote = config_remote::get($signal);
    if ($remote) {

      if (!$signal['is-repeat']) {
        $remote['repeat'] = 0;
        $remote['previous-signal'] = $signal['signal-id'];
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

  static function execute_special_buffer($special_info = null) {
    
    // everything in here already synced
    $remote_id = isset($special_info['remote-id']) ? $special_info['remote-id'] : false;

    //print 'execute_special_buffer' . PHP_EOL;
    $special_signal = implode('', $special_info['buffer']);

    if (!empty($special_signal)) {
      $remote = config_remote::get($special_info);
      $info = gefen_8x8_matrix::get_status();

      $zone = $remote['zone'];
      $output_index = isset($info['kb_outputs'][$zone]) ? $info['kb_outputs'][$zone] : NULL;
      $input_index = isset($info['kb_state'][$output_index]) ? $info['kb_state'][$output_index] : NULL;

      switch ($special_signal) {

        case('cable_1cable_1cable_1'):
          $remote['zone'] = '80inch';
          config_remote::set($remote, $remote_id);
          break;
        case('cable_2cable_2cable_2'):
          $remote['zone'] = 'bedroom';
          config_remote::set($remote, $remote_id);
          break;
        case('cable_3cable_3cable_3'):
          $remote['zone'] = 'workout';
          config_remote::set($remote, $remote_id);
          break;
        case'cable_1':
          gefen_8x8_matrix::set_input_for_zone($remote['zone'], 'kb_cable');
          break;
        case'cable_2':
          gefen_8x8_matrix::set_input_for_zone($remote['zone'], 'co_cable');
          break;
        case'cable_3':
          gefen_8x8_matrix::set_input_for_zone($remote['zone'], 'kb_mac');
          break;
        case'cable_4':
          gefen_8x8_matrix::set_input_for_zone($remote['zone'], 'kb_nix');
          break;
        case'cable_0cable_6':
          hue::strobe(FALSE);
          break;
        case'cable_0cable_0':
          hue::turn_all_lights(FALSE);
          break;
        case'cable_0cable_1':
          hue::turn_all_lights(TRUE);
          //gefen_8x8_matrix::set_input_for_zone(self::$remotes[$remote_code]['zone'], 3);
          break;
        default:
          print "SPECIAL_SOGNAL_NOT_FOUND:" . $special_signal . PHP_EOL;
          $color_hex = preg_match('`^cable_favorite(.*)`', $special_signal, $matches);
          if ($color_hex) {
            //itach::l(print_r($matches[1], TRUE));
            hue::handle_special_signal($matches[1]);
          } else {
            itach::l('not lights');
          }
      }
    }
  }

  static function process_special($signal = null) {
    $is_special = false;
    // IS SPECIAL OR DURING SPECIAL TIMEFRAME
    try {
      $semaphore = sem_get(config_router::$sem_key_special_info, signal_controller::$sem_max, signal_controller::$sem_permissions, signal_controller::$sem_auto_release);
      //$this->log('Attempting to acquire semaphore_check_special');
      sem_acquire($semaphore);
      $special_info = kb::pval(config_router::$key_config_router_special_info);

      if (!is_array($special_info)) {
        kb::pval(config_router::$key_config_router_special_info, array());
      }
      if (!is_null($signal)) {
        $key = config_router::$key_config_router_special_info . $signal['remote-id'];
        $start_signal = $is_special = config_remote::special($signal);

        if ($start_signal) {
          hue::strobe(FALSE);
          $special_info[$key] = array(
            'start' => microtime(true),
            'buffer' => array(),
            'remote-id' => $signal['remote-id'],
          );
          kb::pval(config_router::$key_config_router_special_info, $special_info);
        } else {
          if (isset($special_info[$key]['buffer'])) {
            $old_time = $special_info[$key]['start'];
            $new_time = microtime(true);
            $diff = $new_time - $old_time;
            if ($diff < 2) {
              $is_special = true;
              $special_info[$key]['start'] = $new_time;
              $special_info[$key]['buffer'][] = $signal['signal-name'];
            }
            //print('check special_diff:' . $diff . PHP_EOL);
            kb::pval(config_router::$key_config_router_special_info, $special_info);
          }
        }
      } else {
        // called from cron to check if anything to process
        $is_special = true;
        $new_time = microtime(true);
        $add_back = array();

        foreach ($special_info as $remote_id => $remote) {
          $old_time = $remote['start'];
          $diff = $new_time - $old_time;
          if ($diff > 1.4) {
            if (count($special_info[$remote_id]['buffer'])) {
              //print 'PROCESS SPECIAL BUFFER:' . $remote_id . PHP_EOL;
              config_router::execute_special_buffer($special_info[$remote_id]);
            }
          } else {
            //print 'SPECIAL DIFF NOT GREAT ENOUGH:' . $diff . PHP_EOL;
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
