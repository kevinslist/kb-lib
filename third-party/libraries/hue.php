<?php

class hue {

  static $timeout = 6;

  static function do_hue() {
    $c = json_decode(hue::get('http://192.168.1.251/api/newdeveloper'), TRUE);
    $commands = array();

    foreach ($c['lights'] as $id => $l) {
      $on = $l['state']['on'];
      $url = 'http://192.168.1.251/api/newdeveloper/lights/' . $id . '/state';

      $data = array('on' => !$on);
      $commands[$url] = $data;
    }
    $r = hue::put($commands, $data);
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

}
