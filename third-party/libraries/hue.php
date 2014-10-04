<?php

class hue {

  static $timeout = 6;
  static $developer = '000000001fd544beffffffffb82c643e';
  static $info = array();

  static function init() {
    if (isset($_SESSION['hue-global'])) {
      $_SESSION['hue-global'] = self::get_global_info();
    }
    self::$info = $_SESSION['hue-global'];
    var_export(self::$info);
  }

  static function get_global_info() {
    return json_decode(hue::get('http://192.168.1.251/api/' . self::$developer), TRUE);
  }

  static function turn_all_lights($on = TRUE) {
    self::$info = self::get_global_info();
    //print_r(self::$info);
    $data = array('on' => $on);
    $commands = array();
    foreach (self::$info['lights'] as $id => $l) {
      $url = 'http://192.168.1.251/api/' . self::$developer . '/lights/' . $id . '/state';
      $r = hue::put($url, $data);
      itach::l(print_r($r));
      usleep(5000);
      //$commands[$url] = $data;
    }
    //itach::l(print_r($commands));
    return TRUE;
  }

  static function handle_special_signal($signal = '') {
    $is_hex_code = preg_match('`cable_([0-9]*)cable_([0-9]*)cable_([0-9]*)cable_([0-9]*)cable_([0-9]*)cable_([0-9]*)`', $signal, $matches);
    if ($is_hex_code) {
      self::$info = self::get_global_info();
      $hex_code = '#';
      $full = array_shift($matches);
      foreach ($matches as $code) {
        $hex_code .= $code;
      }
      $hex_code = str_replace("9", "F", $hex_code);
      $hex_code = str_replace("8", "E", $hex_code);
      $hex_code = str_replace("7", "C", $hex_code);
      $hex_code = str_replace("6", "A", $hex_code);
      $hex_code = str_replace("5", "8", $hex_code);
      $hex_code = str_replace("4", "6", $hex_code);
      $hex_code = str_replace("3", "4", $hex_code);
      itach::l('IS HEX CODE:' . $hex_code);
      
      
      $rgb = self::hex2rgb($hex_code);
      $xy = self::rgb2xy($rgb);
      $data = array(
                    'on' => TRUE,
                    'xy' => $xy,
                    "bri" => 254,
                    'colormode' => 'xy',
                  );
 
      itach::l(print_r($rgb, TRUE));
      itach::l(print_r($data, TRUE));
      $commands = array();
      foreach (self::$info['lights'] as $id => $l) {
        $url = 'http://192.168.1.251/api/' . self::$developer . '/lights/' . $id . '/state'; 
        $r = hue::put($url, $data);
        usleep(5000);
        //$commands[$url] = $data;
      }
      itach::l('SHOW HEX CODE:' . $hex_code);
    } else {
      itach::l('not HEX CODE:' . $signal);
    }
  }
  
  
  static function rgb2xy($rgb){
    $X = 0.4124*(int)$rgb['red'] + 0.3576*(int)$rgb['green'] + 0.1805*(int)$rgb['blue'];
    $Y = 0.2126*(int)$rgb['red'] + 0.7152*(int)$rgb['green'] + 0.0722*(int)$rgb['blue'];
    $Z = 0.0193*(int)$rgb['red'] + 0.1192*(int)$rgb['green'] + 0.9505*(int)$rgb['blue'];
    $x_ret = ($X + $Y + $Z) == 0 ? 0 : ($X / ($X + $Y + $Z));
    $y_ret = ($X + $Y + $Z) == 0 ? 0 : ($Y / ($X + $Y + $Z));
    
    return array($x_ret, $y_ret);
  }

  function hex2rgb_old($hex) {
    $hex = str_replace("#", "", $hex);
    
    
    if (strlen($hex) == 3) {
      $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
      $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
      $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
      $r = hexdec(substr($hex, 0, 2));
      $g = hexdec(substr($hex, 2, 2));
      $b = hexdec(substr($hex, 4, 2));
    }
    $rgb = array($r, $g, $b);
    //return implode(",", $rgb); // returns the rgb values separated by commas
    return $rgb; // returns an array with the rgb values
  }
  
  
  function hex2rgb($hexStr) {
    $hexStr = preg_replace("/[^0-9A-Fa-f]/", '', $hexStr); // Gets a proper hex string
    $rgbArray = array();
    if (strlen($hexStr) == 6) { //If a proper hex code, convert using bitwise operation. No overhead... faster
        $colorVal = hexdec($hexStr);
        $rgbArray['red'] = 0xFF & ($colorVal >> 0x10);
        $rgbArray['green'] = 0xFF & ($colorVal >> 0x8);
        $rgbArray['blue'] = 0xFF & $colorVal;
    } elseif (strlen($hexStr) == 3) { //if shorthand notation, need some string manipulations
        $rgbArray['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
        $rgbArray['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
        $rgbArray['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));
    } else {
        return false; //Invalid hex color code
    }
    return $rgbArray;
}

  static function do_hueg() {
    $commands = array();

    $info = json_decode(hue::get('http://192.168.1.251/api/' . self::$developer . '/groups/0'), TRUE);
    $commands = array();

    //foreach ($info as $id => $l) {
    $group_info = $info;

    $on = !$group_info['action']['on'];
    $url = 'http://192.168.1.251/api/' . self::$developer . '/groups/0/action';

    $data = array('on' => false);
    $data['transitiontime'] = 1;
    if ($on) {
      $data['bri'] = 255;
      $data['sat'] = 255;
      $data['hue'] = 62535;
      $data['colormode'] = 'hs';
      $data['effect'] = 'none';
    }
    $commands[$url] = $data;

    //}
    $r = hue::put($commands);

    return $r;
  }

  static function do_hue() {
    $commands = array();
    /*
      $data = array(
      'lights' => array('1', '2'),
      'name' => 'kbgroup',
      );

      $commands[$url] = $data;
     * 
     */
    $on = TRUE;
    for ($i = 0; $i < 10; $i++) {
      for ($j = 1; $j < 7; $j++) {
        $url = 'http://192.168.1.251/api/' . self::$developer . '/lights/' . $j . '/state';
        $data = array('on' => $on);
        $data['transitiontime'] = 0;
        /*
          if ($on) {
          $data['bri'] = 255;
          $data['sat'] = 255;
          $data['hue'] = 65535;
          }
         * 
         */
        hue::put($url, $data);
        usleep(25);
      }
      usleep(25);
      $on = !$on;
    }
    die('kb' . $i);

    $commands = array();

    foreach (self::$info['lights'] as $id => $l) {
      var_export($l);
      die();
      $on = !$l['state']['on'];
      $url = 'http://192.168.1.251/api/' . self::$developer . '/lights/' . $id . '/state';
      $data = array('on' => !true);
      $data['transitiontime'] = 1;
      if ($on) {
        $data['bri'] = 255;
        $data['sat'] = 255;
        $data['hue'] = 62535;
      }
      $commands[$url] = $data;
    }
    $r = hue::put($commands);
    return $r;
  }

  static function group_create() {

    $data = array(
        'lights' => array('1', '2'),
        'name' => 'kbgroup',
    );

    $commands[$url] = $data;
  }

  static function post($put_url, $put_data = array()) {

    if (is_array($put_url)) {
      $ch = curl_multi_init();
    } else {
      $ch = curl_init();
    }



    if (is_array($put_url)) {
      $curl_connections = array();
      foreach ($put_url as $url => $data) {
        $json_body = json_encode($data);
        $curl_connections[$url] = curl_init();
        curl_setopt($curl_connections[$url], CURLOPT_URL, $url);
        curl_setopt($curl_connections[$url], CURLOPT_POSTFIELDS, $json_body);

        curl_setopt($curl_connections[$url], CURLOPT_CONNECTTIMEOUT, self::$timeout);
        curl_setopt($curl_connections[$url], CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_connections[$url], CURLOPT_HEADER, 0);
        //curl_setopt($curl_connections[$url], CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl_connections[$url], CURLOPT_POST, 1);



        curl_multi_add_handle($ch, $curl_connections[$url]);
      }
    } else {
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$timeout);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      //curl_setopt($curl_connections[$url], CURLOPT_CUSTOMREQUEST, "PUT");
      curl_setopt($ch, CURLOPT_POST, 1);
      $json_body = json_encode($put_data);
      curl_setopt($ch, CURLOPT_URL, $put_url);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);
    }

    // execute the handles
    if (is_array($put_url)) {
      $running = null;
      do {
        curl_multi_exec($ch, $running);
        curl_multi_select($ch);
        usleep(80);
      } while ($running > 0);

      // get content and remove handles
      foreach ($curl_connections as $id => $c) {
        $r[$id] = curl_multi_getcontent($c);
        curl_multi_remove_handle($ch, $c);
      }
    } else {
      $r = curl_exec($ch);
    }



    if (is_array($put_url)) {
      curl_multi_close($ch);
    } else {
      curl_close($ch);
    }

    return $r;
  }

  static function put($put_url, $put_data = array()) {

    if (is_array($put_url)) {
      $ch = curl_multi_init();
    } else {
      $ch = curl_init();
    }



    if (is_array($put_url)) {
      $curl_connections = array();
      foreach ($put_url as $url => $data) {
        $json_body = json_encode($data);
        $curl_connections[$url] = curl_init();
        curl_setopt($curl_connections[$url], CURLOPT_URL, $url);
        curl_setopt($curl_connections[$url], CURLOPT_POSTFIELDS, $json_body);

        curl_setopt($curl_connections[$url], CURLOPT_CONNECTTIMEOUT, self::$timeout);
        curl_setopt($curl_connections[$url], CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_connections[$url], CURLOPT_HEADER, 0);
        curl_setopt($curl_connections[$url], CURLOPT_CUSTOMREQUEST, "PUT");




        curl_multi_add_handle($ch, $curl_connections[$url]);
      }
    } else {
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$timeout);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

      $json_body = json_encode($put_data);
      curl_setopt($ch, CURLOPT_URL, $put_url);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $json_body);
    }

    // execute the handles
    if (is_array($put_url)) {
      $running = null;
      do {
        curl_multi_exec($ch, $running);
        curl_multi_select($ch);
        usleep(80);
      } while ($running > 0);

      // get content and remove handles
      foreach ($curl_connections as $id => $c) {
        $r[$id] = curl_multi_getcontent($c);
        curl_multi_remove_handle($ch, $c);
      }
    } else {
      $r = curl_exec($ch);
    }



    if (is_array($put_url)) {
      curl_multi_close($ch);
    } else {
      curl_close($ch);
    }

    return $r;
  }

  static function get($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$timeout);
    curl_setopt($ch, CURLOPT_URL, $url);

    /*
      $ca_cert_path = dirname(__FILE__) . '/certs/';
      curl_setopt($ch, CURLOPT_CAPATH, $ca_cert_path);
      $post = array(
      'access_token' => urlencode($access_token),
      );
      foreach ($post as $key => $value) {
      $post_string .= $key . '=' . $value . '&';
      }
      $post_string = rtrim($post_string, '&');
      curl_setopt($ch, CURLOPT_POST, count($post));
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
     * 
     */
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
  }

  static function delete($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$timeout);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
  }

}
