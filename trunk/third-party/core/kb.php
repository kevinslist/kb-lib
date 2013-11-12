<?php

/**
  /**
 * Common calls/variables used in BSG apps
 *
 * @author kboydstu
 */
spl_autoload_register('kb::autoload');

class kb {

  static function asset($type = null, $file = null, $module = null, $template = null) {
    $path = 'assets/';
    $path .= (empty($type) ? '' : "{$type}/");
    $path .= (empty($file) ? '' : "{$file}");

    if ($module && !file_exists(dirname($_SERVER['SCRIPT_FILENAME']) . "/" . $path)) {
      $path = 'assets/';
      $path .= (empty($module) ? '' : "{$module}/");
      $path .= (empty($type) ? '' : "{$type}/");
      $path .= (empty($file) ? '' : "{$file}");
    }
    if ($template && !file_exists(dirname($_SERVER['SCRIPT_FILENAME']) . "/" . $path)) {
      $path = 'assets/';
      $path .= (empty($module) ? '' : "{$module}/");
      $path .= (empty($template) ? '' : "templates/{$template}/");
      $path .= (empty($type) ? '' : "{$type}/");
      $path .= (empty($file) ? '' : "{$file}");
    }

    return self::home($path);
  }

  static function img($file = null, $module = null, $template = null) {
    return self::asset('images', $file, $module, $template);
  }

  static function js($file = null, $module = null, $template = null) {
    return self::asset('js', $file, $module, $template);
  }

  static function css($file = null, $module = null, $template = null) {
    return self::asset('css', $file, $module, $template);
  }

  static function app_name() {
    return self::config('app_name');
  }

  static function center_name() {
    return self::config('center_name');
  }

  static function home($path = null) {
    return base_url() . (empty($path) ? '' : $path);
  }

  static function is_cron() {
    return !isset($_SERVER["SERVER_NAME"]);
  }

  static function config($key) {
    return self::ci()->config->item($key);
  }

  static function get_client_html_tag() {
    $html = '<html>';
    self::ci()->load->library('user_agent');
    $browser = strtolower(self::ci()->agent->browser());
    if (strpos($browser, 'explo')) {
      $version = (int) self::ci()->agent->version();
      $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
      if ($version < 9 && !strpos($agent, 'trident/5.0')) {
        $html = '<html class="ie8">';
      }
    }
    return $html;
  }
  static function view($path, $vars = array()) {
    return self::ci()->build_content($path, $vars);
  }

  static function build_content($path, $vars) {
    return self::ci()->build_content($path, $vars);
  }

  static function get_template_content($path, $vars) {
    return self::ci()->build_content(bsg::template() . $path, $vars);
  }

  static function template() {
    return bsg_controller::$templates_dir . '/' . bsg_controller::$template;
  }

  static function add_css($css) {
    self::ci()->add_css($css);
  }

  static function add_js($js) {
    self::ci()->add_js($js);
  }

  static function set_rule($field_id = null, $field_label = null, $rule_str = null) {
    self::ci()->form_validation->set_rules($field_id, $field_label, $rule_str);
  }
  static function valid_uupic($uupic = null){
    return preg_match('`^[0-9]{9}$`', $uupic);
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

  static function client_uupic() {
    return isset(self::ci()->client->uupic) ? self::ci()->client->uupic : NULL;
  }

  static function get_orgs($additional_orgs = NULL) {
    $orgs = array();
    $org_results = self::db_array('SELECT org FROM vworgs ORDER BY org ASC');
    foreach ($org_results as $org) {
      $orgs[$org['org']] = $org['org'];
    }
    if(!empty($additional_orgs)){
      foreach($additional_orgs as $org){
        $orgs[$org] = $org;
      }
      sort($orgs);
    }
    return $orgs;
  }

  static function get_rooms($building_number = null) {
    if (!is_null($building_number)) {
      if (isset(self::$room_cache[$building_number])) {
        $rooms = self::$room_cache[$building_number];
      } else {
        self::ci()->db->distinct();
        self::ci()->db->select('RoomNo');
        self::ci()->db->order_by('RoomNo');
        self::ci()->db->where(array('BldgNo' => $building_number));
        self::ci()->db->where(array('RoomNo !=' => 'null'));
        $query = self::ci()->db->get('VwFacBldgTbl');
        $rooms = array();
        //$result[''] = ' [Unset]';
        if ($query->num_rows()) {
          foreach ($query->result_array() as $row) {
            $rooms[$row['RoomNo']] = $row['RoomNo'];
          }
        }
        self::$room_cache[$building_number] = $rooms;
      }
    } else {
      $rooms = array();
    }
    return $rooms;
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

  static function db_get_options($table_name = null, $key=null, $value=null, $conditions = NULL, $convert_varchar = FALSE) {
    $results = array();
    $fields = $key == $value ? $key : "$key, $value";
    self::ci()->db->select($fields);
    if(!empty($conditions)){
      foreach ($conditions as $cond_name => $cond_value) {
        self::ci()->db->where($cond_name, $cond_value);
      }
    }
    self::ci()->db->order_by('CAST(' . $value . ' as varchar)', 'ASC');
    $query = self::ci()->db->get($table_name);
    $results_lower = bsg_model::lc($query->result_array());

    foreach($results_lower as $option){
      $results[$option[$key]] = $option[$value];
    }
    return $results;
  }
  static function db_get_one($table_name = null, $params = null, $index_by = null, $order_by = null) {
    $res = bsg::db_get($table_name, $params, $index_by, $order_by);
    return empty($res) ? $res : reset(bsg::db_get($table_name, $params, $index_by, $order_by));
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
    $results = bsg_model::lc($query->result_array());
    if (!empty($index_by)) {
      $results = bsg_model::ir($results, $index_by);
    }

    return $results;
  }
  static function db_update_all($table = null, $params = null) {
    $return = self::ci()->db->update($table, $params);
    return $return;
  }

  static function db_update($table = null, $params = null, $conditions = null) {
    $return = FALSE;
    if(empty($conditions)){
      $return = self::db_insert($table, $params);
    }else{
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
      $results = bsg_model::ir($results, $index_by);
    }
    return $results;
  }

  static function db_lower_resultset_keys($resultset = array()){
    $results = array();
    if(!empty($resultset) && is_array($resultset)){
      foreach ($resultset as $k) {
        $results[] = self::db_lower_row_keys($k);
      }
    }
    return $results;
  }

  static function db_lower_row_keys($in_row = array()){
    $return_row = array();
    if(!empty($in_row) && is_array($in_row)){
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
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
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
