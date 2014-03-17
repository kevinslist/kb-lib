<?php

spl_autoload_register('kb::autoload');

class kb {
  static $template_name = NULL;

  static function view($path, $vars = array()) {
    return self::ci()->load->view($path, $vars, TRUE);
  }

  static function is_cron() {
    return !isset($_SERVER['SERVER_NAME']);
  }

  static function config($key) {
    return self::ci()->config->item($key);
  }

  static function set_rule($field_id = null, $field_label = null, $rule_str = null) {
    self::ci()->form_validation->set_rules($field_id, $field_label, $rule_str);
  }

  static function guid($name_space = null) {
    $length = 25;
    $upper = "ABCDEFGHIJKLMNOPQRSTUVWZYZ";
    $characters = $upper . "0123456789" . strtolower($upper);
    $real_string_legnth = strlen($characters) - 1;
    $string = "";

    for ($p = 0; $p < $length; $p++) {
      $string .= $characters[mt_rand(0, $real_string_legnth)];
    }
    return empty($name_space) ? $string : $name_space . $string;
  }
  
  static function is_post() {
    return isset($_POST) && !empty($_POST);
  }

  static function validate_form() {
    self::ci()->form_validation->run();
    return validation_errors('<p class="bsg-form-error">', '</p>');
    ;
  }

  static function session_data($key = null, $value = null) {
    return is_null($key) ? null : self::ci()->client->session_data($key, $value);
  }

  static function save_session_data($value = null) {
    $guid = self::guid();
    while (!is_null(self::ci()->client->session_data($guid))) {
      $guid = self::guid();
    }
    self::ci()->client->session_data($guid, $value);
    return $guid;
  }
  
  static function db_unlock(){
    kb::db_exec('UNLOCK TABLES');
  }

  static function db_delete($table_name = null, $params = null) {
    $result = FALSE;
    if (!empty($params)) {
      foreach ($params as $name => $value) {
        self::ci()->db->where($name, $value);
      }
    }
    $result = self::ci()->db->delete($table_name);
    return $result;
  }

  static function db_get_options($table_name = null, $key = null, $value = null, $conditions = NULL, $convert_varchar = FALSE) {
    $results = array();
    $fields = $key == $value ? $key : "$key, $value";
    self::ci()->db->select($fields);
    if (!empty($conditions)) {
      foreach ($conditions as $cond_name => $cond_value) {
        self::ci()->db->where($cond_name, $cond_value);
      }
    }
    self::ci()->db->order_by('CAST(' . $value . ' as varchar)', 'ASC');
    $query = self::ci()->db->get($table_name);

    foreach ($query->result_array() as $option) {
      $results[$option[$key]] = $option[$value];
    }
    return $results;
  }

  static function db_get_one($table_name = null, $params = null, $index_by = null, $order_by = null) {
    $res = kb::db_get($table_name, $params, $index_by, $order_by);
    return empty($res) ? $res : reset(kb::db_get($table_name, $params, $index_by, $order_by));
  }

  static function db_get($table_name = null, $params = null, $index_by = null, $order_by = null) {
    $results = array();
    if (!empty($params)) {
      foreach ($params as $name => $value) {
        self::ci()->db->where($name, $value);
      }
    }
    if (!empty($order_by)) {
      self::ci()->db->order_by($order_by);
    }
    $query = self::ci()->db->get($table_name);
    $results = $query->result_array();
    if (!empty($index_by)) {
      $results = self::ir($results, $index_by);
    }

    return $results;
  }

  static function db_update_all($table = null, $params = null) {
    $return = self::ci()->db->update($table, $params);
    return $return;
  }

  static function db_update($table = null, $params = null, $conditions = null) {
    $return = FALSE;
    if (empty($conditions)) {
      $return = self::db_insert($table, $params);
    } else {
      foreach ($conditions as $name => $value) {
        self::ci()->db->where($name, $value);
      }
      $return = self::ci()->db->update($table, $params);
    }
    return $return;
  }

  static function db_insert($table = null, $params = null) {
    self::ci()->db->insert($table, $params);
    return self::ci()->db->insert_id();
  }

  static function db_array($sql = null, $params = null, $db_name = null, $index_by = NULL) {

    if (!empty($db_name)) {
      self::ci()->temp_db = self::ci()->load->database($db_name, TRUE);
      $r = self::ci()->temp_db->query($sql, $params);
    } else {
      $r = self::ci()->db->query($sql, $params);
    }
    $results = self::db_lower_resultset_keys($r->result_array());
    if (!empty($index_by)) {
      $results = self::ir($results, $index_by);
    }
    return $results;
  }

  static function db_lower_resultset_keys($resultset = array()) {
    $results = array();
    if (!empty($resultset) && is_array($resultset)) {
      foreach ($resultset as $k) {
        $results[] = self::db_lower_row_keys($k);
      }
    }
    return $results;
  }

  static function db_lower_row_keys($in_row = array()) {
    $return_row = array();
    if (!empty($in_row) && is_array($in_row)) {
      foreach ($in_row as $keyname => $value) {
        $return_row[strtolower($keyname)] = $value;
      }
    }
    return $return_row;
  }

  static function db_row($sql = null, $params = null, $db_name = null) {
    $results = reset(self::db_array($sql, $params, $db_name));
    return $results ? $results : array();
  }

  static function db_value($sql = null, $params = null, $db_name = null) {
    $row = self::db_row($sql, $params, $db_name);
    return reset($row);
  }

  static function db_values($sql = null, $params = null, $db_name = null) {
    $values = array();
    $rows = self::db_array($sql, $params, $db_name);
    foreach ($rows as $row) {
      $values[] = reset($row);
    }
    return $values;
  }
  
  static function db_exec($sql, $p = NULL, $return_insert_id = FALSE){
    $ret = NULL;
    if(empty($p)){
      $ret = self::ci()->db->query($sql);
    }else{
      $ret = self::ci()->db->query($sql, $p);
    }
    if($ret && $return_insert_id){
      $ret = self::ci()->db->insert_id();
    }
    return $ret;
  }

  static function ir($rows, $index_by) {
    $result_array = array();
    foreach ($rows as $row_index => $row_values) {
      $result_array[$row_values[$index_by]] = $row_values;
    }

    return $result_array;
  }

  static function ci() {
    return get_instance();
  }

  static function is_dev() {
    return ENVIRONMENT == ENV_DEV;
  }

  static function is_stage() {
    return ENVIRONMENT == ENV_STAGE;
  }

  static function is_uat() {
    return ENVIRONMENT == ENV_UAT;
  }

  static function is_prod() {
    return ENVIRONMENT == ENV_PROD;
  }

  static function dump($var) {
    echo kb::is_cron() ? "\r\n" : '<pre>';
    var_dump($var);
    echo kb::is_cron() ? "\r\n" : '</pre>';
  }

  public static function autoload($class) {
    $found = false;
    $paths = array(
        'core' => strtolower(dirname(__FILE__) . DIRECTORY_SEPARATOR . $class . '.php'),
        'forms' => strtolower(dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'forms/' . $class . '.php'),
        'library' => strtolower(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'libraries/' . $class . '.php'),
        'controller' => strtolower(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'controllers/' . $class . '.php'),
    );
    foreach ($paths as $k => $path) {
      if (is_readable($path)) {
        require_once($path);
        $found = true;
        break;
      }
    }

    return $found;
  }

  static $room_cache = array();

}
