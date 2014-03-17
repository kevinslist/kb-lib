<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class kb_db_mysql_driver extends CI_DB_mysql_driver {


	function __construct($params)
	{
    $secured_params = $params;
		if (!empty($params['kb_prop_name'])){
      $secured_params = kb_db_props::get_props($params['kb_prop_name']);
		}
		parent::__construct($secured_params);
	}

}
