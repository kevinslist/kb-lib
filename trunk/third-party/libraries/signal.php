<?php

class signal {

  static $last_checked_full_signal = 0;
  static $last_checked_repeat_signal = 0;
  static $last_signal_validated = null;

  public function __construct() {
    parent::__construct();
  }

  public function valid(&$signal = NULL) {
    $is_valid = false;
    $valid_time = self::valid_signal_time($signal);
    if ($valid_time) {
      $signal['remote_command_signal_name'] = config_channel::valid_signal_id($signal['remote_command_signal_id']);
      if ($signal['remote_command_signal_name']) {
        $not_repeat_or_volume_send = (!$signal['remote_command_is_repeat'] || preg_match('`_volume_`i', $signal['remote_command_signal_name']));
        // not repeat or volume repeat
        if ($not_repeat_or_volume_send) {
          //print 'SIGNAL.IS>VALID.SIGNAL_TIME>$not_repeat_or_volume_send' . PHP_EOL;
          if (!$signal['remote_command_is_repeat']) {
            self::$last_checked_full_signal = (int) $signal['remote_command_time_sent'];
          } else {
            self::$last_checked_repeat_signal = (int) $signal['remote_command_time_sent'];
          }
          $is_valid = true;
        } else {
          //print 'SIGNAL.NOT>VALID.SIGNAL_TIME>WRONG_REPEAT_SIGNAL' . PHP_EOL;
        }
      }
    }
    return $is_valid;
  }

  public function valid_signal_time($signal = null) {
    $current_time = time();
    $inserted = (int) $signal['remote_command_inserted_time'];
    $diff = $current_time - $inserted;
    $do_return = false;
    if($diff < 5){
      $time_sent = (int) $signal['remote_command_time_sent'];
      $full_diff = $time_sent - self::$last_checked_full_signal;
      $repeat_diff = $time_sent - self::$last_checked_repeat_signal;
      // 5 = 5 miniseconds
      $signal['valid-time'] = $full_diff > 3;
      if ($signal['valid-time'] && $signal['remote_command_is_repeat']) {
        $signal['valid-time'] = $repeat_diff > 2;
      }
      $do_return = $signal['valid-time'] ? $time_sent : false;
    }else{
      print 'SIGNAL IN QUEUE IS TOO OLD...' . PHP_EOL;
    }

    return $do_return;
  }

}
