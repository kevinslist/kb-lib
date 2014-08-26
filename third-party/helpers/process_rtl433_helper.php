<?php
/*
 * itach api id: 057d6b19-5f2c-4deb-bd7c-31f659caaf4e
 * for web interface: https://irdatabase.globalcache.com
 * https://irdatabase.globalcache.com/api/v1/057d6b19-5f2c-4deb-bd7c-31f659caaf4e/manufacturers
 */
class process_rtl433{
  static $script_command = NULL;
  static $process = NULL;
  static $pipes = NULL;
  static $do_quit = FALSE;
  static $buffer = '';
  static $current_signal = NULL;
  static $previous_signal = NULL;
  
  static $signal_started = FALSE;
  
  static $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w"),
    );
  
    //2 => array("file", "/dev/null", "w"),
  static function start(){
    self::$process = proc_open(self::$script_command, self::$descriptorspec, self::$pipes);
    
    echo "CHANNEL CODE COUNT:" . count(self::$channel_codes) . PHP_EOL;

    if (is_resource(self::$process)) {
      $in = fgets(self::$pipes[1]);
      while (!self::$do_quit && !feof(self::$pipes[1])) {
        self::process_input($in);
        $in = fgets(self::$pipes[1]);
      }
    }
  }
  
  static function process_input($s){
    self::$buffer .= trim($s);

    if(!self::$signal_started){
      $pos = strpos(self::$buffer, '#1:');
      if($pos !== FALSE){
        self::$signal_started = TRUE;
        self::process_buffer();
      }else{
        print 'CLEAR-BUFFER:' . self::$buffer . PHP_EOL;
        self::$buffer = '';
      }
    }else{
      self::process_buffer();
    }
  }
  
  static function process_buffer(){
    $pos = strpos(self::$buffer, ';');
    if($pos !== FALSE){
      $pulse = substr(self::$buffer, 0, $pos+1);
      self::process_pulse($pulse);
    }else{
      $pos = strpos(self::$buffer, '|');
      self::process_signal_end();
      self::$signal_started = FALSE;
    }
    if($pos !== FALSE){
      self::$buffer = substr(self::$buffer, $pos+1);
    }
  }
  
  static function process_pulse($p){
    $p_str = substr($p, 1, strlen($p)-2);
    $p_pieces = explode(':', $p_str);
    
    $pulse = array(
        'length' => (int)$p_pieces[5],
        'distance_from_last' => (int)$p_pieces[1],
        'num' => (int)$p_pieces[0],
    );
    
    self::process_add_pulse($pulse);    
    
  }
  
  static function process_add_pulse($pulse){
    if(!isset(self::$current_signal['header-pulse'])){
      self::$current_signal['header-repeat'] = 0;
      self::$current_signal['header-pulse'] = $pulse;
      self::$current_signal['header-length'] = $pulse['length'];
    }else{
      if(empty(self::$current_signal['pulses'])){
        self::$current_signal['average_pulse_length'] = $pulse['length'];
        self::$current_signal['average_pulse_distance_total'] = 0;
      }
      if(self::$current_signal['header-repeat'] == 0 && $pulse['length'] > (self::$current_signal['average_pulse_length'] -8) && $pulse['length'] < (self::$current_signal['average_pulse_length'] + 8)){
        self::$current_signal['pulses'][] = $pulse;
        self::$current_signal['average_pulse_distance_total'] += $pulse['distance_from_last'];
      }elseif($pulse['length'] > (self::$current_signal['header-length'] -15) && $pulse['length'] < (self::$current_signal['header-length'] + 15)){
        self::$current_signal['header-repeat']++;
        self::check_send_signal();
      }
      
      
    }
  }
  
  static function process_signal_end(){
    $repeat = self::check_send_signal();
    print 'SIGNAL COMPLETE' . PHP_EOL;
    //print_r(self::$current_signal);
    if(!$repeat){
      self::$previous_signal = self::$current_signal;
      self::$current_signal = array(
                                'pulses'=>array(), 
                              );
    }
  }
  
  static function check_send_signal(){
    $repeat = FALSE;
    if(empty(self::$current_signal['signal_id'])){
      $num_pulses = count(self::$current_signal['pulses']);
      if($num_pulses > 2){
        $avg_pulse_distance = self::$current_signal['average_pulse_distance_total'] / $num_pulses;
        self::$current_signal['signal_id'] = '';
        foreach(self::$current_signal['pulses'] as $p){
          if($p['distance_from_last'] > $avg_pulse_distance){
            $id_next = '1';
          }else{
            $id_next = '0';
          }
          self::$current_signal['signal_id'] .= $id_next;
        }
      }else{
        self::$current_signal = self::$previous_signal;
        //self::$current_signal['signal_id'] = 'repeat';
        $repeat = TRUE;
      }
    }
    $sid = self::$current_signal['signal_id'];
    $mt = microtime(true);
    if(!isset(self::$channel_last_sent[$sid])){
      self::$channel_last_sent[$sid] = 0;
    }
    $lmt = self::$channel_last_sent[$sid];
    $diff = 1000 * ($mt - $lmt);
    if($diff > 1200){
      self::$channel_last_sent[$sid] = $mt;
      $channel_code = self::$channel_codes[$sid];
      print 'SS( ' . $diff . ' )(' . $channel_code . '):' . $channel_code . PHP_EOL;
      itach::init($channel_code);
    }else{
      print 'NET:( ' . $diff . ' )' . PHP_EOL;
    }
    
    return $repeat;
  }
  
  static $channel_last_sent = array();
  
  // http://customer.comcast.com/remotes/
  // press tv or aux, press setup till 2 blinks, enter code, two blinks good
  // TV SET TO: BRAND: TOSHIBA - CODE 10156
  // AUX SET TO: BRAND: PIONEER - CODE 31384
  static $channel_codes = array(
    "10101000000000110" => "cable_power",
    "11101000000001010" => "cable_channel_up",
    "10011000000000010" => "cable_channel_down",
    "100000010111111010101100010100111" => "tv_volume_up",
    "100000010111111010111100010000111" => "tv_volume_down",
    "100000010111111010000100011110111" => "tv_mute",
    "11010100000000101" => "cable_favorite",
    "11011110000000000" => "cable_my_dvr",
    "10101100000001010" => "cable_on_demmand",
    "10101110000001100" => "cable_page_up",
    "11101110000000100" => "cable_page_down",
    "11000100000000111" => "cable_ok_select",
    "10010110000001001" => "cable_up_arrow",
    "11110110000000110" => "cable_right_arrow",
    "11010110000000001" => "cable_down_arrow",
    "10110110000001110" => "cable_left_arrow",
    "10111100000001000" => "cable_rewind",
    "11011100000000100" => "cable_fast_forward",
    "11101100000000010" => "cable_play",
    "10011100000001100" => "cable_stop",
    "11111100000000000" => "cable_pause",
    "11000110000000011" => "cable_record",
    "10111110000001111" => "cable_live",
    "10011110000001000" => "cable_jump_back",
    "10000110000001011" => "cable_guide",
    "11100110000000101" => "cable_info",
    "11001100000000110" => "cable_menu",
    "10100100000001011" => "cable_exit",
    "10100110000001101" => "cable_help",
    "11100100000000011" => "cable_last",
    "11000000000001111" => "cable_1",
    "10100000000000111" => "cable_2",
    "11100000000001011" => "cable_3",
    "10010000000000011" => "cable_4",
    "11010000000001101" => "cable_5",
    "10110000000000101" => "cable_6",
    "11110000000001001" => "cable_7",
    "10001000000000001" => "cable_8",
    "11001000000001110" => "cable_9",
    "10000000000000000" => "cable_0",
    "10010100000001101" => "cable_tv_vcr",
    "10000001000000011" => "cable_hd_zoom",
    "10100010000000011" => "cable_pip_on_off",
    "11100010000001101" => "cable_pip_swap",
    "10010010000000101" => "cable_pip_move",
    "11010010000001001" => "cable_pip_channel_up",
    "10110010000000001" => "cable_pip_channel_up",
    "10110100000001001" => "cable_lock",
    "11001110000000010" => "cable_day_minus",
    "10001110000001010" => "cable_day_plus",
    "101100101100110100011100011000111" => "aux_power",
    "101100101100110100010100111010110" => "aux_rewind",
    "101100101100110101100100100110110" => "aux_fast_forward",
    "101100101100110100000100111110110" => "aux_stop",
    "101100101100110101010111001010001" => "aux_play_pause",
    "101100101100110100010111011010001" => "aux_ok_select",
    "101100101100110100000010111111010" => "aux_dir_arrow",
    "101100101100110101000010101111010" => "aux_info_menu_exit_last",
    "101100101100110100001100111100110" => "aux_tv_vcr",
    "101100101100110100101000010101111" => "aux_volume_up",
    "101100101100110101101000000101111" => "aux_volume_down",
    "101100101100110101000100101110110" => "aux_channel_up",
    "101100101100110100100100110110110" => "aux_channel_down",
    "101100101100110100111100010000111" => "aux_mute",
    "101100101100110100000000011111111" => "aux_1",
    "101100101100110101000000001111111" => "aux_2",
    "101100101100110100100000010111111" => "aux_3",
    "101100101100110101100000000111111" => "aux_4",
    "101100101100110100010000011011111" => "aux_5",
    "101100101100110101010000001011111" => "aux_6",
    "101100101100110100110000010011111" => "aux_7",
    "101100101100110101110000000011111" => "aux_8",
    "101100101100110100001000011101111" => "aux_9",
    "101100101100110101001000001101111" => "aux_0",
    "100000010111111010100100010110111" => "tv_power",
    "100000010111111010000000111111110" => "tv_menu",
    "100000010111111010011100011000111" => "tv_info",
    "100000010111111010100000110111110" => "tv_up_arrow",
    "100000010111111011100000100111110" => "tv_down_arrow",
    "100000010111111011001100001100111" => "tv_right_arrow",
    "100000010111111011011100001000111" => "tv_left_arrow",
    "100000010111111010001101011100101" => "tv_exit",
    "100000010111111011000101001110101" => "tv_help",
    "100000010111111011111000000001111" => "tv_tv_vcr",
    "100000010111111011100101000110101" => "tv_pip_channel_down",
  );

  
}
