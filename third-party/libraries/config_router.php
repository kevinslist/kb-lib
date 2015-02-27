<?php

class config_router {

  static $special_buffer = null;
  static $special_buffer_timeout = null;
  static $special_initing_remote_id = 'none';
  static $memory_counter = 0;
  static $zone_in_help = null;
  static $zone_in_help_previous_input = null;

  static function check_signal_queue() {
    $signals = kb::db_array('SELECT * FROM remote_commands WHERE remote_command_processed = ?', array(false));
    for ($i = 0; $i < count($signals); $i++) {
      if (signal::valid($signals[$i])) {
        self::execute_signal($signals[$i]);
      } else {
        kb::db_delete('remote_commands', array('remote_command_key' => $signals[$i]['remote_command_key']));
      }
    }
    self::process_special_buffer();
    if (self::$memory_counter++ > 550) {
      self::$memory_counter = 0;
      print "memory_get_usage:" . memory_get_usage() . '    {PEAK:} ' . memory_get_peak_usage() . PHP_EOL;
    }
  }

  static function process_special_buffer() {

    if (!is_null(self::$special_buffer_timeout)) {
      $current_time = microtime(true);
      $diff = $current_time - self::$special_buffer_timeout;
      if ($diff > 1.8) {
        //print 'DO SPECIAL QUEUE!!! then delete...:' . $diff . PHP_EOL;
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
      if(gefen_8x8_matrix::is_roku($remote['zone'])){
        if(!roku::route($signal, $remote)){
          itach::send_signal($signal, $remote);
        }
      }elseif (!is_null(self::$zone_in_help) && $remote['zone'] == self::$zone_in_help) {
        self::process_help_command($signal, $remote);
      } else {
        itach::send_signal($signal, $remote);
      }
    } else {
      print 'CONFIG_ROUTER NO REMOTE FOUND:' . PHP_EOL;
      print_r($signal);
      print PHP_EOL;
    }
  }

  static function process_help_command($signal = null, $remote = null) {
    switch ($signal['remote_command_signal_name']) {
      case 'cable_exit' :
        self::$zone_in_help = null;
        $previous_input = (self::$zone_in_help_previous_input == 'kb_nix') ? 'kb_cable' : self::$zone_in_help_previous_input;
        print 'exit out of help!:' . $previous_input . PHP_EOL;
        gefen_8x8_matrix::set_input_for_zone($remote['zone'], $previous_input);
        break;

      case 'cable_last' :
        $command = 'nohup ' . KB_APP_PATH . 'application/scripts/send_key.sh "Control_L+bracketleft" 2> /dev/null > /dev/null &';
        $output = system($command);
        break;
      case 'cable_ok_select' :
        $command = 'nohup ' . KB_APP_PATH . 'application/scripts/send_key.sh "Return" 2> /dev/null > /dev/null &';
        $output = system($command);
        break;
      case'cable_page_down':
        $command = 'nohup ' . KB_APP_PATH . 'application/scripts/send_key.sh "Tab" 2> /dev/null > /dev/null &';
        $output = system($command);
        break;
      case'cable_page_up':
        $command = 'nohup ' . KB_APP_PATH . 'application/scripts/send_key.sh "shift+Tab" 2> /dev/null > /dev/null &';
        $output = system($command);
        break;
      case 'cable_favorite':
        $command = 'nohup ' . KB_APP_PATH . 'application/scripts/send_f11.sh 2> /dev/null > /dev/null &';
        $output = system($command);
        break;
      case'cable_down_arrow':
        $command = 'nohup ' . KB_APP_PATH . 'application/scripts/send_key.sh "Down" 2> /dev/null > /dev/null &';
        $output = system($command);
        break;
      case'cable_up_arrow':
        $command = 'nohup ' . KB_APP_PATH . 'application/scripts/send_key.sh "Up" 2> /dev/null > /dev/null &';
        $output = system($command);
        break;
      case'cable_left_arrow':
        $command = 'nohup ' . KB_APP_PATH . 'application/scripts/send_key.sh "Left" 2> /dev/null > /dev/null &';
        $output = system($command);
        break;
      case'cable_right_arrow':
        $command = 'nohup ' . KB_APP_PATH . 'application/scripts/send_key.sh "Right" 2> /dev/null > /dev/null &';
        $output = system($command);
        break;
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
      $matrix_info = gefen_8x8_matrix::$info;

      $zone = $remote['zone'];
      $output_index = isset($matrix_info['kb_outputs'][$zone]) ? $matrix_info['kb_outputs'][$zone] : NULL;
      $input_index = isset($matrix_info['kb_output_state'][$output_index]) ? $matrix_info['kb_output_state'][$output_index] : NULL;
      print 'execute_special_buffer:' . $special_signal . PHP_EOL;
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
        case 'cable_5':
          gefen_8x8_matrix::set_input_for_zone($remote['zone'], 'ps3');
          break;
        case 'cable_6':
          gefen_8x8_matrix::set_input_for_zone($remote['zone'], 'roku');
          break;
        case'cable_info':
          self::$zone_in_help_previous_input = $matrix_info['kb_output_state_by_name'][$remote['zone']];
          gefen_8x8_matrix::set_input_for_zone($remote['zone'], 'kb_nix');
          $command = 'nohup ' . KB_APP_PATH . 'application/scripts/firefox_fullscreen.sh 2> /dev/null > /dev/null &';
          exec($command);
          self::$zone_in_help = $remote['zone'];
          break;
        case'cable_0cable_6':
          hue::strobe(FALSE);
          break;
        case'cable_0cable_0':
          hue::turn_all_lights(FALSE);
          break;
        case'cable_1cable_1':
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
      if ($special_started) {
        if ($is_remote) {
          $is_special = true;
          if (!$is_repeat) {
            self::$special_buffer[] = $signal;
          }
        }
      }
    }

    if ($is_special) {
      self::$special_buffer_timeout = microtime(true) - 0.8;
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

/*  matrix info
 * 
 *         )
{CRON}    [kb_inputs] => Array
{CRON}        (
{CRON}            [INPUT1] => 1
{CRON}            [INPUT2] => 2
{CRON}            [ps3] => 3
{CRON}            [denon] => 4
{CRON}            [kb_mac] => 5
{CRON}            [kb_nix] => 6
{CRON}            [kb_cable] => 7
{CRON}            [co_cable] => 8
{CRON}        )
{CRON}    [kb_outputs] => Array
{CRON}        (
{CRON}            [80inch] => 1
{CRON}            [bedroom] => 2
{CRON}            [workout] => 3
{CRON}            [denon] => 4
{CRON}            [computer] => 5
{CRON}            [OUTPUT6] => 6
{CRON}            [OUTPUT7] => 7
{CRON}            [OUTPUT8] => 8
{CRON}        )
{CRON}    [kb_output_state] => Array
{CRON}        (
{CRON}            [1] => 8
{CRON}            [2] => 7
{CRON}            [3] => 7
{CRON}            [4] => 5
{CRON}            [5] => 7
{CRON}            [6] => 7
{CRON}            [7] => 7
{CRON}            [8] => 7
{CRON}        )
{CRON}    [kb_output_state_by_name] => Array
{CRON}        (
{CRON}            [80inch] => co_cable
{CRON}            [bedroom] => kb_cable
{CRON}            [workout] => kb_cable
{CRON}            [denon] => kb_mac
{CRON}            [computer] => kb_cable
{CRON}            [OUTPUT6] => kb_cable
{CRON}            [OUTPUT7] => kb_cable
{CRON}            [OUTPUT8] => kb_cable
{CRON}        )
{CRON})



 */