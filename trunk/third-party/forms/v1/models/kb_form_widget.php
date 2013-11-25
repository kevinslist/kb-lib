<?php


/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */

define('BSG_USER_INPUT_CLASS', ' bsg-user-input'); // leave space before for appending to other classes

class kb_form_widget {
	public $classes			= array();
	public $attributes	= array();
  public $form        = null;

  public $id          = null;
  public $name        = null;
  public $label       = null;
  public $label_suffix= null;
  public $form_value  = null;
  public $db_value    = null;

  public $reversed    = false;
  public $settings    = null;
  public $error       = false;
	public $readonly		= FALSE;
	public $required		= FALSE;
  public $noversion		= FALSE;

  public function __construct($settings = array(), $form = null){
    $this->form = $form;
		if(!isset($settings['readonly'])){
			$settings['readonly'] = FALSE;
		}else{
			$this->readonly = $settings['readonly'];
		}
    if(!isset($settings['noversion'])){
			$settings['noversion'] = FALSE;
		}else{
			$this->noversion = $settings['noversion'];
		}
		if(isset($settings['classes'])){
			if(is_array($settings['classes'])){
				$this->classes = $settings['classes'];
			}else{
				$this->classes[] = $settings['classes'];
			}
		}
		if(isset($settings['attributes'])){
			if(is_array($settings['attributes'])){
				$this->attributes = $settings['attributes'];
			}else{
				$this->attributes[] = $settings['attributes'];
			}
		}
		if(isset($settings['value'])){
			$this->set_value_db($settings['value']);
		}
		if(isset($settings['regex'])){
      $this->classes[] = 'bsg-user-input-regex';

			$this->attributes['bsg-regex-pattern'] = 'data-bsg-regex-pattern="' . $settings['regex']['pattern'] . '"';
      if(isset($settings['regex']['modifiers'])){
        $this->attributes['bsg-regex-modifiers'] = 'data-bsg-regex-modifiers="' . $settings['regex']['modifiers'] . '"';
      }
		}
		if(isset($settings['submit-on-enter'])){
      $this->classes[] = 'do-submit-on-enter';
			$this->attributes['data-bsg-input-submit-on-enter'] = 'data-bsg-input-submit-on-enter="1"';
		}

    $this->settings = $settings;
  }

  public function validate(){
    return null;
  }

  public function get_value_db(){
		$t = is_string($this->db_value) ? trim($this->db_value) : $this->db_value;
		if(is_string($this->db_value) && '' == $t){
			$t = null;
		}
    return is_null($t) ? null : $t;
  }

  public function get_value_form(){
    return !isset($this->form_value) ? '' : $this->form_value;
  }

  public function set_value_db($value){
    $this->db_value = $value;
    $this->_set_value_form($value);
  }

  public function _set_value_db($value){
    $this->db_value = $value;
  }

  public function set_value_form($value){
    $this->form_value = $value;
    $this->_set_value_db($value);

  }

  public function _set_value_form($value){
    $this->form_value = $value;
  }

	public function set_readonly($readonly = NULL, $overwrite = null){
    $this->readonly = $readonly || $this->settings['readonly'];
    if($overwrite){
      $this->readonly = $readonly;
    }
		$this->settings['readonly'] = $this->readonly;
	}
	public function set_required($required = FALSE){
		$this->required = $required;
		$this->settings['required'] = $this->required;
		if($this->required){
			$this->classes['required'] = 'required';
		}else{
			unset($this->classes['required']);

		}
	}



	public function get_readonly(){
		return $this->readonly || (isset($this->settings['readonly']) && $this->settings['readonly']);
	}

  public function render(){
    $this->settings['classes'] = implode(' ', $this->classes());
    $this->settings['attributes'] = implode(' ', $this->attributes());

    $label = $this->render_label();
    $input = $this->render_input();
    if( $this->reversed() ){
      $out = $input . $label;
    }else{
      $out = $label . $input;
    }
    return $out;
  }

  public function render_label(){
    $this->settings['full_label'] = $this->label();
    return kb::view('field_label', $this->settings);
  }
  public function render_input(){
    $this->settings['form_value'] = $this->get_value(false);
    return '';
  }

  public function set_value($value = null, $db_format = true){
    if($db_format){
      $this->set_value_db($value);
    }else{
      $this->set_value_form($value);
    }
    return $this;
  }

  public function get_value($db_format=true){
    if($db_format){
      $value = $this->get_value_db();
    }else{
      $value = $this->get_value_form();
    }
    return $value;
  }

  public function label($value = null){
    $ret = $this;
    if(is_null($value)){
      $ret = empty( $this->settings['label'] ) ? '' : $this->settings['label'];//. $this->settings['label_suffix'];
    }

    return $ret;
  }

  public function reversed($value = null){
    $ret = $this;
    if(is_null($value)){
      $ret = empty( $this->settings['reversed'] ) ? false : true;
    }

    return $ret;
  }

  public function add_class($classname = NULL){
    if(!empty($classname)){
      $this->classes[] = $classname;
    }
  }

  public function classes(){
    if(isset($this->settings['real_id'])){
      $this->classes[] = $this->settings['real_id'];
    }
    if(isset($this->settings['type'])){
      $this->classes[] = $this->settings['type'];
    }
    if(isset($this->settings['widget'])){
      switch($this->settings['widget']){
        case 'checkbox':
          $new_class = 'bsg-checkbox';
          break;
        default:
          $new_class = $this->settings['widget'];
          break;
      }
      $this->classes[] = $new_class;
    }
    if( isset($this->settings['required']) && $this->settings['required']){
      $this->classes[] = 'required';
    }
    if( isset($this->settings['error']) && $this->settings['error']){
      $this->classes[] = 'error';
    }
    if($this->get_readonly()){
      $this->classes[] = 'readonly';
    }
    return array_unique($this->classes);
  }

  public function attributes($other = array()){

    if( isset($this->settings['required']) && $this->settings['required']){
      $this->attributes[] = ' required="required"';
    }
    if($this->readonly || (isset($this->settings['readonly']) && $this->settings['readonly']) ){
      $this->attributes[] = ' readonly="readonly"';
    }
    if (isset($this->settings['maxlength'])) {
      $this->attributes[] = ' maxlength="'.$this->settings['maxlength'].'"';
    }
    
    if (!empty($other)) {
      $this->attributes = array_merge($this->attributes, $other);
    }

    return array_unique($this->attributes);
  }


}