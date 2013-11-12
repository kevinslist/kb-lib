<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
spl_autoload_register('kb_loader::autoload');

class kb_loader extends CI_Loader {
  public $package_paths = array();
  
  public function add_template_view_path($template_path){
    array_pop($this->_ci_view_paths);
    $this->_ci_view_paths['application/third_party/bsg/views/templates/' . $template_path . '/'] = true;
    $this->_ci_view_paths['application/views/'] = true;
  }
  
  public function add_form_view_path($form_folder_path){
    array_pop($this->_ci_view_paths);
    $this->_ci_view_paths['application/third_party/bsg/views/bsg-forms/' . $form_folder_path . '/'] = true;
    $this->_ci_view_paths['application/views/'] = true;
  }

	public function add_package_path($path, $view_cascade=TRUE)
	{
		$path = rtrim($path, '/').'/';
    $this->package_paths[] = $path;
		$this->_ci_library_paths[] = $path;
		$this->_ci_model_paths[] = $path;
		$this->_ci_helper_paths[] = $path;
		$this->_ci_view_paths = array($path.'views/' => $view_cascade) + $this->_ci_view_paths;

		// Add config file path
		$config =& $this->_ci_get_component('config');
		$config->_config_paths[] = $path;
	}

	public function database($params = '', $return = FALSE, $active_record = NULL)
	{

		// Grab the super object
		$CI =& get_instance();

		// Do we even need to load the database class?
		if (class_exists('CI_DB') AND $return == FALSE AND $active_record == NULL AND isset($CI->db) AND is_object($CI->db))
		{
			return FALSE;
		}

		require_once(BASEPATH.'database/DB.php');

		$db = DB($params, $active_record);

		// Load extended DB driver
		$custom_db_driver = 'bsg_db_'.$db->dbdriver.'_driver';
		$custom_db_driver_file = dirname(dirname(__FILE__)).'/database/drivers/'.$db->dbdriver.'/'.$custom_db_driver.'.php';

		if (file_exists($custom_db_driver_file))
		{
			require_once($custom_db_driver_file);

			$db = new $custom_db_driver(get_object_vars($db));
                }

		// Return DB instance
		if ($return === TRUE)
		{
			return $db;
		}

		// Initialize the db variable. Needed to prevent reference errors with some configurations
		$CI->db = '';
		$CI->db =& $db;
	}
    
  public static function autoload($class) {
    $found = false;
    $paths = array(
        'core' => strtolower(dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . $class . '.php'),
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

}