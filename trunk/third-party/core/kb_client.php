<?php

        
class kb_client{

  private $bsg_auth = FALSE;
  var $bsg_auth_roles = array();
  var $bsg_auth_permissions = array();
  var $permissions = array();
  var $unauthenticated_name = 'Guest';
  
  public function __construct() {
    if (!kb::is_cron()) {
      ini_set('session.cookie_domain', $_SERVER['HTTP_HOST'] . '; HttpOnly');
      ini_set('session.gc_maxlifetime', 72000);
      ini_set('session.cookie_lifetime', 0);
      ini_set('session.cookie_secure', 'On');
    }
  }

  public function logged_in($set_logged_in = null) {
    $return = $this;

    if (is_null($set_logged_in)) {
      $return = isset($_SESSION[BSG_CLIENT_DID_AUTH_INIT]) && $_SESSION[BSG_CLIENT_DID_AUTH_INIT];
    } else {
      $_SESSION[BSG_CLIENT_DID_AUTH_INIT] = $set_logged_in;
      if(FALSE === $set_logged_in){
        $this->log_out();
      }
    }
    return $return;
  }

  public function log_out(){
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
        if($auid != $this->auid){
          $this->log_out();
          $this->init($auid);
        }
      } else{
        if(!empty($auid)){
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

  function set_info_from_session() {
    $ned_info = $this->session_data('ned-info');
    $this->firstname = $ned_info['firstname'];
    $this->lastname = $ned_info['lastname'];
    $this->uupic = $ned_info['uupic'];
    $this->auid = $ned_info['auid'];
    $this->email = $ned_info['email'];
    $this->phone = empty($ned_info['phone']) ? NULL : $ned_info['phone'];
    $this->orgcode = $ned_info['orgcode'];
    $this->nasaidentitystatus = $ned_info['nasaidentitystatus'];
    $this->center = $ned_info['center'];
    $this->displayname = $ned_info['shortdisplayname'];
    $this->arnumber = $this->x500id = $ned_info['x500id'];
    $this->building = isset($ned_info['building']) ? $ned_info['building'] : NULL;
    $this->room = isset($ned_info['room']) ? $ned_info['room'] : NULL;
    $this->temp_perm = isset($ned_info['temp_perm']) ? $ned_info['temp_perm'] : NULL;
    $this->foreign_national = isset($ned_info['foreign_national']) ? $ned_info['foreign_national'] : NULL;
    $this->citizenship = isset($ned_info['citizenship']) ? $ned_info['citizenship'] : NULL;

    if (bsg::is_dev() || bsg::is_stage()) {
      $this->bsg_auth_settings();
    }
    $bsg_auth_roles = $this->session_data('bsg-auth-roles');
    $bsg_auth_permissions = $this->session_data('bsg-auth-permissions');
    $this->bsg_auth = $this->session_data('bsg-auth');
    $this->bsg_auth_roles = !empty($bsg_auth_roles) ? $bsg_auth_roles : $this->bsg_auth_roles;
    $this->bsg_auth_permissions = !empty($bsg_auth_permissions) ? $bsg_auth_permissions : $this->bsg_auth_permissions;
  }

  /* DEPRECIATED */

  function set_permissions() {
    // define $this->session_data('permissions') in your local client file. each application may vary. permissions should be
    // an array of roles
    if ($this->session_data('permissions')) {
      $this->permissions = $this->session_data('permissions');
    }
  }

  function display_name() {
    return empty($this->displayname) ? $this->unauthenticated_name : $this->displayname;
  }

  function get_uupic() {
    return empty($this->uupic) ? null : $this->uupic;
  }

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
  
  public function get_message_section(){
    $m = $this->get_messages();
    return  empty($m) ? NULL : bsg::view('common/user_messages', array('messages' => $m));
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

    if('bsg-auth-access-application' == $privelege){
      if(in_array($privelege . '-' . $this->nams_id, $this->bsg_auth_permissions)){
        $allowed = TRUE;
      }
    }else{
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

  function bsg_auth_settings() {
    $settings = bsg::config('bsg_auth');
    if (!empty($settings)) {
      $this->bsg_auth = isset($settings['bsg-auth']) && $settings['bsg-auth'] ? TRUE : FALSE;
      $this->nams_id = isset($settings['nams-id']) && !empty($settings['nams-id']) ? $settings['nams-id'] : null;
      $this->nams_id_required = isset($settings['nams-id-required']) && TRUE === $settings['nams-id-required'] ? TRUE : FALSE;

      if ($this->bsg_auth) {
        $this->bsg_auth_set_roles();
        $this->bsg_auth_set_permissions();
      }
      if (!empty($this->nams_id)) {
        $nasa_apps_apache = getenv('NASAUserApplications');
        $nasa_apps_apache = $nasa_apps_apache ? $nasa_apps_apache : '';
        $nasa_apps_server = isset($_SERVER['NASAUserApplications']) ? $_SERVER['NASAUserApplications'] : '';
        $nasa_apps = strlen($nasa_apps_apache) > strlen($nasa_apps_server) ? $nasa_apps_apache : $nasa_apps_server;

        if(is_string($nasa_apps) && strlen($nasa_apps)) {
          $idmax_accounts = explode(';', $nasa_apps);
          if (!empty($idmax_accounts)) {
            foreach ($idmax_accounts as $id) {
                $app_key = $id;
                $this->bsg_auth_permissions['bsg-auth-access-application-' . $app_key] = 'bsg-auth-access-application-' . $app_key;
              if (preg_match('`^[0-9]+$`', $id)) {
              }
            }
            $this->session_data('bsg-auth-permissions', $this->bsg_auth_permissions);
          }
        }
      }
    }
    $this->session_data('bsg-auth', $this->bsg_auth);
  }
  
  function bsg_auth_set_roles() {
    // 1) use same database - or 2) define new config for shared DB (option 1 for now)

    $roles = bsg::db_array('SELECT ar.auth_role_key, ar.auth_role_label
                              FROM auth_user_role aur
                              LEFT JOIN auth_role ar ON aur.auth_role_id=ar.auth_role_id
                              WHERE aur.auth_user_uupic=?', $this->uupic);
    foreach ($roles as $role) {
      $this->bsg_auth_roles[$role['auth_role_key']] = $role['auth_role_label'];
    }


    $this->session_data('bsg-auth-roles', $this->bsg_auth_roles);
  }

  function bsg_auth_set_permissions() {
    // 1) use same database - or 2) define new config for shared DB (option 1 for now)
    // get permissions from direction user->permission assignments
    $permissions = bsg::db_values('SELECT ap.auth_permission_key
                              FROM auth_user_permission aup
                              LEFT JOIN auth_permission ap ON aup.auth_permission_id=ap.auth_permission_id
                              WHERE aup.auth_user_uupic=?', $this->uupic);
    foreach ($permissions as $permission) {
      $this->bsg_auth_permissions[$permission] = $permission;
    }
    if (count($this->bsg_auth_roles)) {
      // get permissions from user->role->permission assignments
      $sql = 'SELECT ap.auth_permission_key
                                FROM auth_role_permission arp
                                LEFT JOIN auth_permission ap ON arp.auth_permission_id=ap.auth_permission_id
                                LEFT JOIN auth_role ar ON arp.auth_role_id=ar.auth_role_id
                                WHERE ar.auth_role_key IN (\'' . implode("', '", array_keys($this->bsg_auth_roles)) . '\')';


      $permissions = bsg::db_values($sql);
      foreach ($permissions as $permission) {
        $this->bsg_auth_permissions[$permission] = $permission;
      }
    }
    $this->session_data('bsg-auth-permissions', $this->bsg_auth_permissions);
  }

  function redirect_saml_login($redirect_url = NULL){
    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $web_root = $saml_login_url = 'https://' . $_SERVER['SERVER_NAME'];
    $target = empty($redirect_url) ? $web_root . $request_uri : $redirect_url;
    $saml_login_url = $web_root . '/Shibboleth.sso/Login?target=' . $target;
    redirect($saml_login_url);
    die();

  }

}
