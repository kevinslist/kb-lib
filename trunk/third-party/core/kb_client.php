<?php
define('KB_CLIENT_UNAUTHENTICATED_STATUS', 'guest');
define('KB_CLIENT_UNAUTHENTICATED_NAME', 'Guest');
define('KB_CLIENT_IS_LOGGED_IN', 'kb_client_is_logged_in');

class kb_client {

  var $uupic = NULL;
  var $id = NULL;
  var $roles = array();
  var $permissions = array();
  var $name = KB_CLIENT_UNAUTHENTICATED_NAME;
  var $email = NULL;
  var $status = KB_CLIENT_UNAUTHENTICATED_STATUS;

  public function __construct() {
    if (!kb::is_cron()) { 
      if(session_status() == PHP_SESSION_NONE){
        session_set_cookie_params(72000, '/', NULL, TRUE, TRUE);
        session_start();
      }
      $this->set_info_from_session();
    }
  }
  
  public function load($data){
    $user_info = array('status'=>KB_CLIENT_UNAUTHENTICATED_STATUS);
    $provider = isset($data['provider']) ? $data['provider'] : NULL;
    switch($provider){
      case 'google':
        $name = 'google_id';
        $value= isset($data[$name]) ? $data[$name] : NULL;
        break;
    }
    if(!empty($name) && !empty($value)){
      $user_info = kb::db_get_one('users', array($name=>$value));
    }
    $client_info = array_merge($data, $user_info);
    $this->session_data('kb-client-info', $client_info);
    $this->set_info_from_session(TRUE);
  }

  public function set_info_from_session($do_lookup = FALSE) {
    $kb_client_info = $this->session_data('kb-client-info');
    if (!empty($kb_client_info)) {
      $this->uupic =  empty($kb_client_info['uupic']) ? NULL : $kb_client_info['uupic'];
      $this->id =     empty($kb_client_info['id']) ? NULL : $kb_client_info['id'];
      $this->name =   empty($kb_client_info['username']) ? KB_CLIENT_UNAUTHENTICATED_NAME : $kb_client_info['username'];
      $this->email =  empty($kb_client_info['email']) ? NULL : $kb_client_info['email'];
      $this->status = empty($kb_client_info['status']) ? KB_CLIENT_UNAUTHENTICATED_STATUS : $kb_client_info['status'];
      $this->set_authorization($do_lookup);
    }
  }

  public function set_authorization($force_new_lookup = FALSE) {
    $this->set_roles($force_new_lookup);
    $this->set_permissions($force_new_lookup);
  }

  public function set_roles($force_new_lookup = FALSE) {
    $this->roles = empty($this->session_data('kb-client-info-roles')) ? array() : $this->session_data('kb-client-info-roles');
    if ($force_new_lookup) {
      $this->roles = kb::db_values('SELECT role_id FROM user_roles WHERE uupic = ?', $this->id);
      $this->session_data('kb-client-info-roles', $this->roles);
    }
  }

  public function set_permissions($force_new_lookup = FALSE) {
    $permissions = empty($this->session_data('kb-client-info-permissions')) ? array() : $this->session_data('kb-client-info-permissions');
    if ($force_new_lookup) {
      $permissions_direct = kb::db_values('SELECT permission_id FROM user_permissions WHERE uupic = ?', $this->uupic);
      $permissions_roles = empty($this->roles) ? array() : kb::db_values('SELECT permission_id FROM roles_permissions
                                          WHERE role_id IN (\'' . implode("', '", array_keys($this->roles)) . '\')');
      $this->permissions = array_merge($permissions_direct, $permissions_roles);
      $this->session_data('kb-client-info-permissions', $this->permissions);
    }
  }

  public function logged_in($set_logged_in = null) {
    $return = $this;
    if (is_null($set_logged_in)) {
      $return = isset($_SESSION[KB_CLIENT_IS_LOGGED_IN]) && $_SESSION[KB_CLIENT_IS_LOGGED_IN];
    } else {
      $_SESSION[KB_CLIENT_IS_LOGGED_IN] = $set_logged_in;
      if (FALSE === $set_logged_in) {
        $this->log_out();
      }
    }
    return $return;
  }

  public function log_out() {
    $this->load->library('session');
    $this->session->sess_destroy();
    $_SESSION = array();
    session_destroy();

    $this->roles = array();
    $this->permissions = array();
    $this->name = KB_CLIENT_UNAUTHENTICATED_NAME;
  }

  function session_data($key = null, $value = null) {
    $ret = null;
    if (is_null($value)) {
      $ret = isset($_SESSION[$key]) ? unserialize($_SESSION[$key]) : null;
    } else {
      $_SESSION[$key] = serialize($value);
    }
    return $ret;
  }

  function clear_session_data($key) {
    unset($_SESSION[$key]);
    return $this;
  }

  /* DEPRECIATED */

  function get_navigation() {
    $nav_items = array();
    return $nav_items;
  }

  function get_top_navigation() {
    $nav_items = array();
    return $nav_items;
  }

  function add_message($message = null, $type = null) {
    $type = empty($type) ? 'plain' : $type;
    $clean_message = $this->clean_messages($message);

    if (!isset($_SESSION['user_messages'][$type]))
      $_SESSION['user_messages'][$type] = array();

    if (is_array($message)) {
      $_SESSION['user_messages'][$type] = array_merge($_SESSION['user_messages'][$type], $clean_message);
    } else {
      $_SESSION['user_messages'][$type][] = $clean_message;
    }
  }

  public function get_message_section() {
    $m = $this->get_messages();
    return empty($m) ? NULL : bsg::view('common/user_messages', array('messages' => $m));
  }

  public static function get_messages() {
    $m = empty($_SESSION['user_messages']) ? array() : $_SESSION['user_messages'];
    $_SESSION['user_messages'] = array();
    return $m;
  }

  public function clean_messages($message) {
    if (is_array($message)) {
      $ret = array();
      foreach ($message as $m) {
        $ret[] = $this->clean_message($m);
      }
    } else {
      $ret = $this->clean_message($message);
    }
    return $ret;
  }

  public function clean_message($message) {
    return strip_tags($message, '<p><a><strong><b><u><li><span><div><hr><i><table><tr><td><th><thead><tbody><style><br><h1><h2><h3><h4><h5>');
  }

  /**
   * privilege checking function
   *
   * @access	public
   * @param		string	$privilege	Privilege to check for
   * @param		boolean	$redirect		Redirect if invalid permissions
   * @param		mixed		$p					Optional parameters
   * @return	boolean	TRUE or FALSE depending if the user has correct privileges
   */
  public function can($privelege = NULL, $redirect = FALSE, $p = NULL) {
    $allowed = FALSE;

    if ('kb-auth-access-application' == $privelege) {
      if (in_array($privelege . '-' . $this->nams_id, $this->bsg_auth_permissions)) {
        $allowed = TRUE;
      }
    } else {
      if ($this->bsg_auth) {
        switch ($privelege) {
          default:
            $role_keys = array_keys($this->roles);
            if (in_array($privelege, $this->permissions) || in_array($privelege, $role_keys)) {
              $allowed = TRUE;
            }
            break;
        }
      } else {
        // old permissions

        switch ($privelege) {
          default:
            if (in_array($privelege, $this->permissions)) {
              $allowed = TRUE;
            }
            break;
        }
      }
    }

    if (!$allowed && $redirect) {
      $this->add_message('Requested action cannot be performed - Access Denied', 'error');
      if (TRUE !== $redirect) {
        $redirect_url = $redirect;
      } else {
        $redirect_url = site_url();
      }
      redirect($redirect_url);
    } else {
      return $allowed;
    }
  }

  function redirect_saml_login($redirect_url = NULL) {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $web_root = $saml_login_url = 'https://' . $_SERVER['SERVER_NAME'];
    $target = empty($redirect_url) ? $web_root . $request_uri : $redirect_url;
    $saml_login_url = $web_root . '/Shibboleth.sso/Login?target=' . $target;
    redirect($saml_login_url);
    die();
  }

}
