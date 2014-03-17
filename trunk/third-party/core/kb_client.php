<?php

define('KB_CLIENT_IS_LOGGED_IN', 'kb_client_is_logged_in');

class kb_client {

  var $id = NULL;
  var $roles = array();
  var $permissions = array();
  var $name = 'Guest';
  var $email = NULL;
  var $status = 'guest';

  public function __construct() {
    if (!kb::is_cron()) {
      ini_set('session.cookie_domain', $_SERVER['HTTP_HOST'] . '; HttpOnly');
      ini_set('session.gc_maxlifetime', 72000);
      ini_set('session.cookie_lifetime', 0);
      ini_set('session.cookie_secure', 'On');
      $this->set_info_from_session();
    }
  }

  public function set_info_from_session($do_lookup = FALSE) {
    $kb_client_info = $this->session_data('kb-client-info');
    if (!empty($kb_client_info)) {
      $this->id = $kb_client_info['id'];
      $this->name = $kb_client_info['name'];
      $this->email = $kb_client_info['email'];
      $this->status = $kb_client_info['email'];
      $this->set_authorization($do_lookup);
    }
  }

  public function set_authorization($force_new_lookup = FALSE) {
    $this->set_roles($force_new_lookup);
    $this->set_permissions($force_new_lookup);
  }

  public function set_roles($force_new_lookup = NULL) {
    $this->roles = empty($this->session_data('kb-client-info-roles')) ? array() : $this->session_data('kb-client-info-roles');
    if ($force_new_lookup) {
      $this->roles = kb::db_values('SELECT role_id FROM user_roles WHERE user_id = ?', $this->id);
      $this->session_data('kb-client-info-roles', $this->roles);
    }
  }

  public function set_permissions($do_lookup = FALSE) {
    $permissions = empty($this->session_data('kb-client-info-permissions')) ? array() : $this->session_data('kb-client-info-permissions');
    if ($force_new_lookup) {
      $permissions_direct = kb::db_values('SELECT permission_id FROM user_permissions WHERE user_id = ?', $this->id);
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

    $this->bsg_auth_roles = array();
    $this->bsg_auth_permissions = array();
    $this->displayname = $this->unauthenticated_name;
  }


  public function initzz($auid = null, $uupic = null) {

    if (!kb::is_cron()) {
      if (!isset($_SESSION)) {
        session_start();
      }
      $auid = $this->get_auid_from_server();
      if ($this->logged_in()) {
        $this->set_info_from_session();
        if ($auid != $this->auid) {
          $this->log_out();
          $this->init($auid);
        }
      } else {
        if (!empty($auid)) {
          $this->init($auid);
        }
      }
    }
    $this->load->model('VwNedExtract');
    if (!empty($auid)) {
      $p = VwNedExtract::auid($auid);
    } elseif (!empty($uupic)) {
      $p = VwNedExtract::uupic($uupic);
    }
    if (!empty($p)) {
      $this->uupic = $p['uupic'];
      $this->bsg_auth_settings();
      $this->session_data('ned-info', $p);
      $this->set_info_from_session();
      $this->logged_in(TRUE);
    }
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

    if ('bsg-auth-access-application' == $privelege) {
      if (in_array($privelege . '-' . $this->nams_id, $this->bsg_auth_permissions)) {
        $allowed = TRUE;
      }
    } else {
      if ($this->bsg_auth) {
        switch ($privelege) {
          default:
            $role_keys = array_keys($this->bsg_auth_roles);
            if (in_array($privelege, $this->bsg_auth_permissions) || in_array($privelege, $role_keys)) {
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
