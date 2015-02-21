<?php

class config_router {

  static $special_buffer = null;
  static $special_buffer_timeout = null;
  static $special_initing_remote_id = 'none';

  static function check_signal_queue() {
    $signals = kb::db_array('SELECT * FROM remote_commands WHERE remote_command_processed = ?', array(false));
    foreach ($signals as $signal) {
      if (signal::valid($signal)) {
        self::execute_signal($signal);
      } else {
        kb::db_delete('remote_commands', array('remote_command_key' => $signal['remote_command_key']));
      }
    }
    self::process_special_buffer();
  }

  static function process_special_buffer() {

    if (!is_null(self::$special_buffer_timeout)) {
      $current_time = microtime(true);
      $diff = $current_time - self::$special_buffer_timeout;
      if ($diff > 1.8) {
        print 'DO SPECIAL QUEUE!!! then delete...:' . $diff . PHP_EOL;
        self::execute_special_buffer();
        self::$special_buffer = null;
        self::$special_buffer_timeout = null;
        self::$special_initing_remote_id = null;
      }
    }
  }

  static function execute_signal($signal) {
    //print 'execute_signal' . PHP_EOL;
    if (!self::check_special($signal)) {
      self::route($signal);
    }
    kb::db_update('remote_commands', array('remote_command_processed' => 1), array('remote_command_key' => (int) $signal['remote_command_key']));
  }

  static function route($signal) {
    // time is fine to send signal
    // only volume repeats past this point

    $remote = config_remote::get($signal);
    if ($remote) {
      itach::send_signal($signal, $remote);
    } else {
      print 'CONFIG_ROUTER NO REMOTE FOUND:' . PHP_EOL;
      print_r($signal);
      print PHP_EOL;
    }
  }

  static function execute_special_buffer() {
    $special_signal = '';
    $signal = null;
    if (count(self::$special_buffer)) {
      foreach (self::$special_buffer as $signal) {
        $special_signal .= $signal['remote_command_signal_name'];
      }
    }

    if (!empty($special_signal)) {
      $remote = config_remote::get($signal);
      $info = gefen_8x8_matrix::get_status();

      $zone = $remote['zone'];
      $output_index = isset($info['kb_outputs'][$zone]) ? $info['kb_outputs'][$zone] : NULL;
      $input_index = isset($info['kb_state'][$output_index]) ? $info['kb_state'][$output_index] : NULL;
      print '$special_signal:' . $special_signal . PHP_EOL;
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

  static function check_special($signal = null) {

    $special_started = !is_null(self::$special_buffer);
    $remote_id = $signal['remote_command_remote_id'];
    $is_repeat = $signal['remote_command_is_repeat'];
    $is_remote = $remote_id == self::$special_initing_remote_id;

    $is_special = config_remote::special($signal);
    if ($is_special) {
      // special trigger pressed
      if (!$is_repeat) {
        self::$special_buffer = array();
        self::$special_initing_remote_id = $remote_id;
      }
    } else {
      if($special_started){
        if($is_remote){
          $is_special = true;
          if(!$is_repeat){
            self::$special_buffer[] = $signal;
          }
        }
      }
    }
    
    if($is_special){
      self::$special_buffer_timeout = microtime(true);
      print 'reset special buffer timeout' . PHP_EOL;
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
