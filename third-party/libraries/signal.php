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
      $signal['signal-name'] = config_channel::valid_remote_code($signal['signal-id']);
      if ($signal['signal-name']) {
        if (!$signal['is-repeat']) {
          self::$last_checked_full_signal = (int) $signal['last-signal'];
        }

        $not_repeat_or_volume_send = (!$signal['is-repeat'] || preg_match('`_volume_`i', $signal['signal-name']));
        // not repeat or volume repeat
        if ($not_repeat_or_volume_send) {
          print 'SIGNAL.IS>VALID.SIGNAL_TIME>$not_repeat_or_volume_send' . PHP_EOL;
          $is_valid = true;
        } else {
          print 'SIGNAL.NOT>VALID.SIGNAL_TIME>WRONG_REPEAT_SIGNAL' . PHP_EOL;
        }
      }
    }
    return $is_valid;
  }

  public function check_special() {
    $current_check_special_time = microtime(true);
    //$this->log('check_special>getlast>:');

    try {
      $semaphore = sem_get(signal_controller::$sem_key_check_special, signal_controller::$sem_max, signal_controller::$sem_permissions, signal_controller::$sem_auto_release);
      //$this->log('Attempting to acquire semaphore_check_special');
      sem_acquire($semaphore);
      $last_sent_check_special = (float) kb::pval(signal_controller::$signal_key_check_special_last);

      if (0 >= $last_sent_check_special) {
        // firsttime
        kb::pval(signal_controller::$signal_key_check_special_last, $current_check_special_time);
        $this->log('SERVER. SIGNAL. CHECK_Special:INIT' . $last_sent_check_special . ':::' . $current_check_special_time);
      } else {
        $diff = $current_check_special_time - $last_sent_check_special;
        if ($diff > 1.1) {
          //$this->log('SERVER. SIGNAL. CHECK_Special:' . $diff);
          kb::pval(signal_controller::$signal_key_check_special_last, $current_check_special_time);
          config_router::route_special();
        }
      }
    } catch (Exception $ex) {
      $this->log('EXCEPTION IN check_special:');
      $this->log(ex);
    } finally {
      sem_release($semaphore);
    }
  }

  public function valid_signal_time($signal = null) {
    $do_return = false;
    $last_sent_new = (int) $signal['last-signal'];
    $full_diff = $last_sent_new - self::$last_checked_full_signal;
    $repeat_diff = $last_sent_new - self::$last_checked_repeat_signal;
    // 5 = 5 miniseconds
    $signal['valid-time'] = $full_diff > 5;

    if ($signal['valid-time'] && $signal['is-repeat']) {
      $signal['valid-time'] = $repeat_diff > 3;
      self::$last_checked_repeat_signal = $last_sent_new;
    }
    $do_return = $signal['valid-time'] ? $last_sent_new : false;

    return $do_return;
  }

}
