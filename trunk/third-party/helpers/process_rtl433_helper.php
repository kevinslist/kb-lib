<?php

/*
 * itach api id: 057d6b19-5f2c-4deb-bd7c-31f659caaf4e
 * for web interface: https://irdatabase.globalcache.com
 * https://irdatabase.globalcache.com/api/v1/057d6b19-5f2c-4deb-bd7c-31f659caaf4e/manufacturers
 */

class process_rtl433 {

  static $script_command = NULL;
  static $script_output = NULL;
  static $script_frequency = NULL;
  static $script_remote = NULL;
  static $process = NULL;
  static $pipes = NULL;
  static $do_quit = FALSE;
  static $buffer = '';
  static $current_signal = array();
  static $previous_signal = array();
  static $signal_started = FALSE;
  static $previous_signal_id = '666';
  static $previous_signal_sent = 0;
  static $repeat_count = 0;
  
  static $aux_count = 0;
  static $aux_menu_count = 0;
  static $aux_dir_count = 0;
  static $aux_menu_signal = '';
  static $aux_last_sent = 0;
  
  static $descriptorspec = array(
      0 => array("pipe", "r"),
      1 => array("pipe", "w"),
      2 => array("pipe", "w"),
  );
  //2 => array("file", "/dev/null", "w"),
  static function start() {
    $command = self::$script_command;
    $command .= '' . self::$script_frequency;
    $command .= ' ' . self::$script_output;

    self::$process = proc_open($command, self::$descriptorspec, self::$pipes);

    echo "CHANNEL CODE COUNT:" . count(self::$channel_codes) . PHP_EOL;
    print $command . PHP_EOL;

    itach::reset_matrix_status();

    if (is_resource(self::$process)) {
      $in = fgets(self::$pipes[1]);
      while (!self::$do_quit && !feof(self::$pipes[1])) {
        self::process_input($in);
        $in = fgets(self::$pipes[1]);
      }
    } else {
      print 'NOT RESOURCE....' . PHP_EOL;
    }
  }

  static function process_input($signal_full = NULL) {
    $signal_trimmed = trim($signal_full);
    $signal = rtrim($signal_trimmed, ';');
    $pos = strpos($signal, ';');
    $sid = 'default';
    
    if ($pos !== FALSE) {
      $pulses = explode(';', $signal);
      $pulse_count = count($pulses);
      $header_pulse = array_shift($pulses);
      $header_data = explode(':', $header_pulse);

      $hl = isset($header_data[5]) ? (int) $header_data[5] : 0;
      $current_time = (int) $header_data[6];

      if ($pulse_count > 15) {
        self::$previous_signal = self::$current_signal;
        //self::$current_signal
        self::$current_signal = array(
            'signal-id' => '',
            'signal-time' => 0,
            'pulses' => array(),
            'header-length' => 0,
            'repeat-count' => 0,
            'count-pulses' => TRUE,
            'pulse-count' => 0,
            'pulse-length' => 0,
            'pulse-distance-total' => 0,
            'pulse-distance-average' => 0,
        );
        self::$current_signal['header-length'] = $hl;
        if ($hl > 1900) {

          foreach ($pulses as $pulse) {
            $pulse_data = explode(':', $pulse);
            if (7 == count($pulse_data)) {
              self::$current_signal['pulses'][] = $pulse_data;
            }
          }

          $first_pulse = array_shift(self::$current_signal['pulses']);
          $pl = $first_pulse[5];
          self::$current_signal['pulse-length'] = $pl;
          self::$current_signal['pulse-count'] = 1;
          //print 'FIRST PULSE LEN:' . $pl . PHP_EOL;

          foreach (self::$current_signal['pulses'] as $p) {
            if (self::$current_signal['count-pulses'] && $p[5] > ($pl - 8) && $p[5] < ($pl + 8)) {
              self::$current_signal['pulse-count'] ++;
              self::$current_signal['pulse-distance-total'] += $p[1];
            } elseif ($p[5] > ($hl - 15) && $p[5] < ($hl + 15)) {
              self::$current_signal['count-pulses'] = FALSE;
            }
          }

          $pad = (int) (self::$current_signal['pulse-distance-total'] / self::$current_signal['pulse-count']);
          self::$current_signal['pulse-distance-average'] = $pad;

          foreach (self::$current_signal['pulses'] as $p) {
            self::$current_signal['signal-id'] .= ($p[1] > $pad ? '1' : '0');
          }
        }

        $sid = self::$current_signal['signal-id'];

        if ($sid == self::$previous_signal_id) {
          self::$current_signal = self::$previous_signal;
          self::$current_signal['repeat-count'] ++;
        }

        if (isset(self::$channel_codes[$sid])) {
          $d = $current_time - self::$previous_signal_sent;
          $do_repeat = ($d > 1009) || ((self::$current_signal['repeat-count'] == 1) && ($d > 500));

          if (TRUE || self::$current_signal['repeat-count'] == 0 || $do_repeat) {
            self::$repeat_count = 0;
            self::$previous_signal_id = $sid;
            self::$previous_signal_sent = $current_time;
            self::$current_signal['repeat-count'] = 0;
            self::$previous_signal = self::$current_signal;
            //print 'SEND[[' . self::$channel_codes[$sid] . '::' . self::$current_signal['repeat-count'] . '::DIFF::' . $d . PHP_EOL;
            self::send_signal($sid);
          } else {
            // print 'FULL_BUT_REPEAT_DIFF:' . $d . ':::::' . self::$current_signal['repeat-count'] . PHP_EOL;
          }
        } else {
          print '__xxx__NOT FOUND:' . $signal_full . PHP_EOL;
        }
      } elseif ($pulse_count = 2 && isset(self::$previous_signal['signal-id'])) {
        self::$repeat_count++;
        $diff = (int) ($current_time - self::$previous_signal_sent);
        //print 'REPEAT COUNT:' . self::$repeat_count . '::DIFF::' . $diff . PHP_EOL;
        if (self::$repeat_count > 6 && $diff > 500) {
          $sid = self::$previous_signal['signal-id'];
          self::$previous_signal_id = $sid;
          self::$previous_signal_sent = $current_time;
          self::$repeat_count = 0;
          self::$current_signal['repeat-count'] = 0;
          //print 'SEND(' . self::$channel_codes[$sid] . ':' . self::$current_signal['repeat-count'] . PHP_EOL;
          self::send_signal($sid);
        }
        return;
        /*
          $sid = self::$previous_signal_id;
          $header_pulse = array_shift($pulses);
          $header_data = explode(':', $header_pulse);
          $current_time = (int)$header_data[6];

         */
      }
    } else {
      print 'NOT SIGNAL:' . $signal . PHP_EOL;
    }
    return;
  }

  static function send_signal($sid = NULL) {
    $did_send = FALSE;
    if (!empty($sid) && isset(self::$channel_codes[$sid])) {
      $aux_check = preg_match('`^aux`i', self::$channel_codes[$sid]);
      $aux_menu_check = preg_match('`^aux_info_menu_exit_last`i', self::$channel_codes[$sid]);
      $aux_dir_check = preg_match('`^aux_dir_arrow`i', self::$channel_codes[$sid]);

      
      if (!$aux_check) {
        self::$aux_last_sent = self::$previous_signal_sent;
        $did_send = itach::init(self::$channel_codes[$sid]);
      }else{
        $aux_diff = self::$previous_signal_sent - self::$aux_last_sent;
        print 'AUX-CHECK:' . $aux_diff . ':::|:::'. self::$aux_count . ':::' . self::$aux_menu_count . '::' . self::$aux_dir_count . ':::::' . self::$channel_codes[$sid] . PHP_EOL;
        
        self::$aux_count++;
        if ($aux_menu_check) {
          self::$aux_menu_count++;
        }
        if ($aux_dir_check) {
          self::$aux_dir_count++;
        }
      }  
      
      
      
      return $did_send;
      
      
      
      
      
      
      
      
      
      
      
      
      
      if ($aux_check) {
        self::$aux_count++;
        if ($aux_menu_check) {
          self::$aux_menu_count++;
        }
        if ($aux_dir_check) {
          self::$aux_dir_count++;
        }
      } else {
        self::$aux_count = 0;
        self::$aux_menu_count = 0;
        self::$aux_dir_count = 0;
      }
      if (!$aux_check) {
        // (!$aux_menu_check && self::$aux_count == 1)
        $did_send = itach::init(self::$channel_codes[$sid]);
      } elseif ($aux_check) {
            print 'AUX-CHECK:' . self::$aux_count . ':::' . self::$aux_menu_count . '::' . self::$aux_dir_count . ':::::' . self::$channel_codes[$sid] . PHP_EOL;
        if (!$aux_menu_check && !$aux_dir_check) {
          if (self::$aux_menu_count + self::$aux_dir_count > 0) {
            
            if (self::$aux_menu_count > 0) {
              $did_send = itach::init(self::$channel_codes[$sid]);
            } elseif (self::$aux_dir_count > 0) {
              if (self::$channel_codes[$sid] == 'aux_last') {
                $code = 'aux_up_arrow';
              } else {
                $code = self::$channel_codes[$sid];
              }
              $did_send = itach::init($code);
            }
          } else {
            if (self::$aux_count > 0) {
              print 'KBSEND HERE' . PHP_EOL . PHP_EOL;
            }elseif (self::$aux_count == 2) {
              self::$aux_count = 0;
            }
            print 'RESET-AUX:' . self::$aux_count . ':::' . self::$aux_menu_count . '::' . self::$channel_codes[$sid] . PHP_EOL;
          }
        } else {
          self::$aux_count++;
          if (self::$aux_count > 1) {
            if (self::$aux_menu_count > 1) {
              self::$aux_menu_count = 0;
            } elseif (self::$aux_dir_count > 1) {
              self::$aux_dir_count = 0;
            }
            print 'IGNORE-AUX-MENU:' . self::$aux_count . ':::' . self::$aux_menu_count . '::' . self::$channel_codes[$sid] . PHP_EOL;
          }
        }
      }
    }
    return $did_send;
  }

  static $channel_last_sent = array();
  // http://customer.comcast.com/remotes/
  // press tv or aux, press setup till 2 blinks, enter code, two blinks good
  // TV SET TO: BRAND: TOSHIBA - CODE 10156
  // AUX SET TO: BRAND: PIONEER - CODE 31384
  static $channel_codes = array(
      "0101000000000110" => "cable_power",
      "1101000000001010" => "cable_channel_up",
      "0011000000000010" => "cable_channel_down",
      "00000010111111010101100010100111" => "tv_volume_up",
      "00000010111111010111100010000111" => "tv_volume_down",
      "00000010111111010000100011110111" => "tv_mute",
      "1010100000000101" => "cable_favorite",
      "1011110000000000" => "cable_my_dvr",
      "0101100000001010" => "cable_on_demmand",
      "0101110000001100" => "cable_page_up",
      "1101110000000100" => "cable_page_down",
      "1000100000000111" => "cable_ok_select",
      "0010110000001001" => "cable_up_arrow",
      "1110110000000110" => "cable_right_arrow",
      "1010110000000001" => "cable_down_arrow",
      "0110110000001110" => "cable_left_arrow",
      "0111100000001000" => "cable_rewind",
      "1011100000000100" => "cable_fast_forward",
      "1101100000000010" => "cable_play",
      "0011100000001100" => "cable_stop",
      "1111100000000000" => "cable_pause",
      "1000110000000011" => "cable_record",
      "0111110000001111" => "cable_live",
      "0011110000001000" => "cable_jump_back",
      "0000110000001011" => "cable_guide",
      "1100110000000101" => "cable_info",
      "1001100000000110" => "cable_menu",
      "0100100000001011" => "cable_exit",
      "0100110000001101" => "cable_help",
      "1100100000000011" => "cable_last",
      "1000000000001111" => "cable_1",
      "0100000000000111" => "cable_2",
      "1100000000001011" => "cable_3",
      "0010000000000011" => "cable_4",
      "1010000000001101" => "cable_5",
      "0110000000000101" => "cable_6",
      "1110000000001001" => "cable_7",
      "0001000000000001" => "cable_8",
      "1001000000001110" => "cable_9",
      "1111111111111111" => "cable_0",
      "0010100000001101" => "cable_tv_vcr",
      "0000001000000011" => "cable_hd_zoom",
      "0100010000000011" => "cable_pip_on_off",
      "1100010000001101" => "cable_pip_swap",
      "0010010000000101" => "cable_pip_move",
      "1010010000001001" => "cable_pip_channel_up",
      "0110010000000001" => "cable_pip_channel_up",
      "0110100000001001" => "cable_lock",
      "1001110000000010" => "cable_day_minus",
      "0001110000001010" => "cable_day_plus",
      "01100101100110100011100011000111" => "aux_power",
      "01100101100110100010100111010110" => "aux_rewind",
      "01100101100110101100100100110110" => "aux_fast_forward",
      "01100101100110100000100111110110" => "aux_stop",
      "01100101100110101010111001010001" => "aux_play_pause",
      "01100101100110100010111011010001" => "aux_ok_select",
      "01100101100110100000010111111010" => "aux_dir_arrow",
      "11110101000010101010011101011000" => "aux_down_arrow",
      "11110101000010101110011100011000" => "aux_right_arrow",
      "11110101000010100110011110011000" => "aux_left_arrow",
      "01100101100110101000010101111010" => "aux_info_menu_exit_last",
      "11110101000010101001110101100010" => "aux_menu",
      "11110101000010100010011111011000" => "aux_last",
      "11110101000010100010111111010000" => "aux_exit",
      "11110101000010101100011100111000" => "aux_info",
      "01100101100110100001100111100110" => "aux_tv_vcr",
      "01100101100110100101000010101111" => "aux_volume_up",
      "01100101100110101101000000101111" => "aux_volume_down",
      "01100101100110101000100101110110" => "aux_channel_up",
      "01100101100110100100100110110110" => "aux_channel_down",
      "01100101100110100111100010000111" => "aux_mute",
      "01100101100110100000000011111111" => "aux_1",
      "01100101100110101000000001111111" => "aux_2",
      "01100101100110100100000010111111" => "aux_3",
      "01100101100110101100000000111111" => "aux_4",
      "01100101100110100010000011011111" => "aux_5",
      "01100101100110101010000001011111" => "aux_6",
      "01100101100110100110000010011111" => "aux_7",
      "01100101100110101110000000011111" => "aux_8",
      "01100101100110100001000011101111" => "aux_9",
      "01100101100110101001000001101111" => "aux_0",
      "00000010111111010100100010110111" => "tv_power",
      "00000010111111010000000111111110" => "tv_menu",
      "00000010111111010011100011000111" => "tv_info",
      "00000010111111010100000110111110" => "tv_up_arrow",
      "00000010111111011100000100111110" => "tv_down_arrow",
      "00000010111111011001100001100111" => "tv_right_arrow",
      "00000010111111011011100001000111" => "tv_left_arrow",
      "00000010111111010001101011100101" => "tv_exit",
      "00000010111111011000101001110101" => "tv_help",
      "00000010111111011111000000001111" => "tv_tv_vcr",
      "00000010111111011100101000110101" => "tv_pip_channel_down",
  );

}
