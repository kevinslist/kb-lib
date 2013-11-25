<?php

/**
 * Description of kb_form
 *
 */
define('KB_FORM_STATUS_ERRORS', 'errors');
define('KB_FORM_STATUS_VALID', 'valid');
define('KB_FORM_STATUS_CANCEL', 'cancel');
define('KB_FORM_STATUS_RESET', 'reset');
define('KB_FORM_STATUS_SUBMIT', 'submit');
define('KB_FORM_STATUS_DELETE', 'delete');
define('KB_FORM_SUBMIT_NAME', 'kb-form-submit');
define('KB_FORM_VIEW_DIRECTORY', 'v1');
spl_autoload_register('kb_form::autoload');
class kb_form{

  public $form_id           = null;
  public $form_title        = null;
  public $fields            = array();
  public $layout            = array();
  public $field_definitions = array();
  public $is_new            = TRUE;
  public $label_suffix      = ':';
  public $buttons           = array('Submit' => KB_FORM_STATUS_SUBMIT, 'Cancel' =>KB_FORM_STATUS_CANCEL); // label => name
  public $buttons_first     = FALSE;
  public $button_index      = 0;
  public $errors            = FALSE;
  public $status            = FALSE;
  public $form_unique_id		= null;
	public $button_submitted	= FALSE;
	public $rules							= array();
	public $readonly					= FALSE;
	public $primary_key				= null;
  public $post_controller   = null;
  public $sub_form          = FALSE;
  public $sub_form_new      = FALSE;
  public $sub_form_id       = '';
  static $sub_form_id_count = 1;
  static $form_widget_directory     = KB_FORM_VIEW_DIRECTORY;
  static $form_view_directory       = KB_FORM_VIEW_DIRECTORY;

  // options
  public $action            = '';
  public $class             = array('kb-form');
  public $method            = 'post';
  public $multipart         = FALSE;
  public $xss               = TRUE;
  public $css_folder        = 'form';

  private $is_unique_extra = '';

  public function __construct($form_id = null, $form_unique_id = null, $primary_key = null, $is_unique_extra = '', $sub_form = FALSE){
    kb::ci()->load->library('form_validation');
    kb::ci()->load->helper('form');
    //kb::ci()->load->helper('kb_form_helper');
    //kb::ci()->add_body_class($form_id . ' kb-form');
    $this->form_id = is_null($form_id) ? strtolower(get_parent_class()) : $form_id;
		if( !is_null($primary_key) ){
			$this->is_new = FALSE;
			$this->primary_key = $primary_key;
		}
    $this->is_unique_extra = $is_unique_extra;
    $this->sub_form = $sub_form;
    $this->init();
    if(!$this->sub_form){
      $this->check_post($form_unique_id);
    }
  }

  public function init(){
    $this->set_field_definitions();
    $this->set_layout();
    $this->construct_fields();
    if( kb::is_post()){
			$this->button_submitted = $_POST[KB_FORM_SUBMIT_NAME];
		}
   
    $this->set_form_rules();
    $this->set_form_rules_css();
    return $this;
  }

  public function check_post($form_unique_id = null){
    if( kb::is_post()){
      // will validate
      $redirect     = empty($this->post_controller) ? current_url() : $this->post_controller;

      if( is_null($form_unique_id) ){
        $form_unique_id = 'kbFORM' . kb::guid();
        $redirect .= '/' . $form_unique_id;
      }
			//$this->button_submitted = $_POST[KB_FORM_SUBMIT_NAME];
			// set in init();
			// stupid ie acts differently than other browsers, the button's contents overrides the content's value. If you need to change what the
			// text of the button says, you need to add it here.
      if( (strcasecmp(KB_FORM_STATUS_CANCEL, $this->button_submitted) == 0)) {
        // cancel pressed
        $form_data       = null;
        $status         = KB_FORM_STATUS_CANCEL;
			}elseif( (strcasecmp(KB_FORM_STATUS_DELETE, $this->button_submitted) == 0)) {
				$status         = KB_FORM_STATUS_DELETE;
      }else{
				if( isset($_FILES) && !empty($_FILES)){
					foreach($_FILES as $key=>$uploads){
						$_POST[$key]['uploads'] = $uploads;
					}
				}
        $this->values($_POST, false); // save file uploads, FROM form not DB
				$form_data  = $this->values();
        kb::validate_form();
        $this->validate();
        $status     = empty($this->errors) ? KB_FORM_STATUS_VALID : $this->errors;
      }
      kb::session_data($form_unique_id, array('form-data'=>$form_data, 'status'=>$status, 'button-submitted'=> $this->button_submitted));
      redirect($redirect);
    }else{
      // check for saved form values
      // could be done in child class
      $data = kb::session_data($form_unique_id);
      if(!is_null($data)){
				$this->form_unique_id = $form_unique_id;
        $form_data = $data['form-data'];
        if( is_array($form_data) ){
          $this->values($form_data);
        }
        $status = $data['status'];
				if (isset($data['button-submitted'])) {
					$this->button_submitted = $data['button-submitted'];
				}
        if(is_array($status) && !empty($status)){
          $this->set_errors($status);
          $this->status = KB_FORM_STATUS_ERRORS;

        }elseif( !empty($status) ){
          $this->status = $status;
        }
      }
    }
    return $this->errors;
  }

  public function set_errors($errors){
    $this->errors = $errors;
    foreach($this->fields as $key=>$field){
      if(isset($errors[$key]) && !empty($errors[$key])){
        if(is_array($errors[$key])){
          $error_str = '<p>' . implode('</p><p>', $errors[$key]) . '</p>';
        }else{
          $error_str = $errors[$key];
        }
      }else{
        $error_str = FALSE;
      }
      $field->settings['error'] = $error_str;
    }
  }

  public function status($new_status = null){
		$ret = $this;
		if( is_null($new_status)){
			$ret = $this->status;
		}elseif(!empty($this->form_unique_id)){
			$form_data  = $this->values();
			$status     = $new_status;
			kb::session_data($this->form_unique_id, array('form-data'=>$form_data, 'status'=>$status));
		}
		return $ret;
  }

  public function clean_up(){
    kb::session_data($this->form_unique_id, array('form-data'=>array(), 'status'=>FALSE));
    return $this;
  }

  public function validate(){
    $this->errors = array();
    foreach($this->field_definitions as $key=>$field){
      if(isset($this->fields[$key])){
        $error = $this->fields[$key]->validate();
      }
      $open_error = '<span class="form-validation" data-related-field="' . $key . '">';
      $close_error = '</span>';

      if(empty($error)){
        $m = form_error($key, $open_error, $close_error);
      }else{
        $m = $open_error . $error . $close_error;
      }
      if(!empty($m)){
        $this->errors[$key] = $m;
      }
    }
    return $this->errors;
  }

  public function edit($values = null){
    $this->is_new = FALSE;
    if( !is_null($values)){
      $this->values($values);
    }
    return $this;
  }

  public function buttons($buttons = null){
    $ret = $this;
    if( !is_null($buttons) ){
      $this->buttons = $buttons;
    }else{
      $ret = $this->buttons;
    }
    return $ret;
  }

  public function title($form_title = null){
    $ret = $this;
    if( !is_null($form_title) ){
      $this->form_title = $form_title;
    }else{
      $ret = $this->form_title;
    }
    return $ret;
  }

	public function set_field_options($field_id, $options){
		if(isset($this->fields[$field_id])){
				$this->fields[$field_id]->set_options($options);
		}
		$this->field_definitions[$field_id]['options'] = $options;
	}

  public function remove_layout_item($remove_key, $parent_keys = array(), $layout = NULL, $new_parent = NULL){
    $layout = empty($layout) ? $this->layout : $layout;
    if(!empty($new_parent)){
      $parent_keys[] = $new_parent;
    }
    foreach($layout as $key=>$object){
      if(is_array($object)){
        //$parent_keys[] = $key;
        $this->remove_layout_item($remove_key, $parent_keys, $object, $key);
      }elseif($key === $remove_key){
        $parent_keys[] = $key;
        $this->do_remove_layout_item($parent_keys);
        return;
      }
    }
  }

  public function do_remove_layout_item($keys = array()){
    if(!empty($keys)){
      $unset = '';
      foreach($keys as $k){
        $unset .= '["' . $k . '"]';
      }
      eval('unset($this->layout' . $unset . ');');
    }
  }



  public function set_layout(){
    return $this;
  }

  public function set_field_definitions(){
    return $this;
  }

  public function construct_fields(){
    foreach($this->field_definitions as $key => $settings){
			$this->add_field($key);
		}
    return $this;
  }

	public function add_field_layout($key = null, $position = null){
		$object = $this->add_field($key);

    if( !empty($this->form_unique_id) ){
      // might have data
      $data = kb::session_data($this->form_unique_id);
      if(isset($data[$key])){
        $this->fields[$key]->set_value($data[$key]);
      }
    }
    
		if(!empty($object)){
			if(empty($position)){
				$this->layout[$key] = $key;
			}
		}
	}

	public function add_field($key = null){
		$object = null;
		if(!empty($key)){
			$settings = isset($this->field_definitions[$key]) ? $this->field_definitions[$key] : null;
			if(!empty($settings)){
				$settings['id']           = $key;
				$settings['label_suffix'] = $this->label_suffix;
				switch($settings['widget']){
          case 'file_path':
						$object = new kb_form_file_path($settings, $this);
            break;
          case 'kb-camera-image':
						$object = new kb_form_widget_camera_image($settings, $this);
            break;
          case 'checklist':
						$object = new kb_form_widget_checklist($settings, $this);
            break;
          case 'table_list':
						$object = new kb_form_widget_table_list($settings);
						break;
          case 'one-to-many':
						$object = new kb_form_widget_one_to_many($settings);
						break;
					case 'approver_workflow':
						$object = new kb_form_widget_approver_workflow($settings, $this);
						break;
					case 'fieldset_text':
						$object = new kb_form_widget_fieldset_text($settings);
						break;
					case 'keywords':
						$object = new kb_form_widget_keywords($settings);
						$this->multipart = TRUE;
						break;
					case 'attachment_multi_versioned':
						$object = new kb_form_widget_attachment_multi_versioned($settings);
						$this->multipart = TRUE;
						break;
					case 'attachment':
						$object = new kb_form_widget_attachment($settings);
						$this->multipart = TRUE;
						break;
					case 'multi_building_room':

						$object = new kb_form_widget_building_room_multi($settings);
						break;
					case 'userlookup_multi':
						$object = new kb_form_widget_input_userlookup_multi($settings);
						break;
					case 'userlookup':
						$object = new kb_form_widget_input_userlookup($settings);
						break;
					case 'lookup':
						$object = new kb_form_widget_input_lookup($settings);
						break;
					case 'date':
						$object = new kb_form_widget_input_date($settings);
						break;
					case 'select':
						$object = new kb_form_widget_select($settings);
						break;

					case 'button':
						$object = new kb_form_widget_input_button($settings);
						break;
					case 'checkbox':
						$object = new kb_form_widget_input_checkbox($settings);
						break;
					case 'textarea':
						$object = new kb_form_widget_textarea($settings);
						break;
          case 'label':
            //$object = new kb_form_widget_label($settings);
            $object = new kb_form_widget($settings);
            break;
					case 'text':
					default:
						$object = new kb_form_widget_input_text($settings);
						break;
				}
				$this->fields[$key] = $object;
			}
		}
		return $object;
	}

	public function set_form_rules(){
		$rules = $this->get_rules();
    if($this->sub_form){
     
    }
		foreach($rules as $key=>$rule){
      if ( substr_count($rule, 'is_unique') > 0 ) {
          preg_match('/is\_unique\[(.*?)\]/', $rule, $match);
          $rule = str_replace($match[1], $match[1] . $this->is_unique_extra, $rule);
      }
			kb::set_rule($key, $this->field_definitions[$key]['label'], $rule);
		}
	}



	// use this to override validation in child
  public function get_rules(){
		$this->rules = $this->get_rules_from_layout();
		return $this->rules;
  }

	public function get_rules_from_layout($set = null){
		$rules = array();
    $elements = is_null($set) ? $this->layout : $set;
    foreach($elements as $key=>$field_or_container){
      if(is_array($field_or_container)){
        $rules = array_merge($rules, $this->get_rules_from_layout($field_or_container));
      }else{
        if(isset($this->fields[$key])){
          if( isset($this->field_definitions[$key]['rules'])){
            $rules[$key] = $this->field_definitions[$key]['rules'];
          }

        }
      }
    }
		return $rules;
	}


	public function set_form_rules_css(){
		foreach($this->rules as $key=>$rule_str){
			$rules = explode('|', strtolower($rule_str));
			foreach($rules as $rule){
				$r = trim($rule);
				switch($r){
					case 'required':
						$this->fields[$key]->set_required(TRUE);
						break;
					case 'validation_rule':
						$this->fields[$key]->classes[] = 'validation_rule';
						$this->fields[$key]->attributes[] = 'validation_rule';
						break;
				}
			}
		}
	}

  public function render_sub_form($readonly = false, $new = FALSE, $sub_form_id = null){
    if($readonly || $this->readonly){
			$this->readonly = TRUE;
      $this->class[] = 'readonly';
    }
    $this->sub_form_id = is_null($sub_form_id) ? self::$sub_form_id_count++ : $sub_form_id;
    $this->sub_form_new = $new;

    $form_elements = array();
    $is_clone = '_cl0ne_' === $sub_form_id;

    foreach($this->layout as $key=>$element){
      $form_elements[] = $this->render_element($key, $element, $this->readonly, $is_clone);
    }

    $vars['form_id'] = $this->form_id;
    $vars['form_fields_fieldset_content'] = implode(" \r", $form_elements);
		$vars['classes'] = $this->get_classes();

    $vars['form_fields_fieldset'] = kb::view('sub_form_fields_fieldset', $vars);
		return kb::view('sub_form', $vars);
  }


  public function render($form_title = null, $readonly = false){

    if($this->sub_form){
      return $this->render_sub_form($readonly);
    }

    if(!empty($form_title)){
      $this->title($form_title);
    }
    if($readonly || $this->readonly){
			$this->readonly = TRUE;
      $this->class[] = 'readonly';
    }
    $form_elements = array();

    foreach($this->layout as $key=>$element){
      $form_elements[] = $this->render_element($key, $element, $this->readonly);
    }

    $vars['form_attributes'] = array('class'=>$this->get_classes(), 'id'=>$this->form_id);;
    $vars['buttons']  = $this->readonly ? '' : $this->render_buttons();
    $vars['form_id'] = $this->form_id;
    $vars['form_title'] = $this->title();
    $vars['form_fields_fieldset_content'] = implode(" \r", $form_elements);
		$vars['classes'] = $this->get_classes();

    $vars['form_fields_legend'] = kb::view('form_fields_legend', $vars);
    $vars['form_fields_fieldset'] = kb::view('form_fields_fieldset', $vars);

    $vars['buttons_first'] = $this->buttons_first;
    $vars['multipart'] = $this->multipart;
    $vars['action'] = $this->action;

		if($this->readonly){
      $form =  kb::view('form_readonly', $vars);
		}else{
      $form =  kb::view('form', $vars);
		}
    return  $form;
  }

  public function render_element($key = null, $element = null, $readonly = FALSE, $is_clone = FALSE){
    $element_str = '';
    if(is_array($element)){
      $element_str .= $this->render_container($key, $element, $readonly, $is_clone);
    }elseif( isset($this->fields[$element]) ){
      $element_str .= $this->render_field($element, $this->fields[$element], $readonly, $is_clone);
    }else{
      $element_str .= '<div>!MISSING FIELD Definition from layout: ' . $element . '</div>';
    }
    return $element_str;
  }

  public function render_container($key = null, $element = null, $readonly = FALSE, $is_clone = FALSE){
    $vars = array();
    $vars['content'] = '';
    $vars['id']     = $key;
    $classes = isset($element['classes']) ? $element['classes'] : '';
    $classes = !empty($classes) ? (is_array($classes) ? implode(' ', $classes) : $classes) : '';
    $vars['classes'] = $readonly ? $classes . ' readonly' : $classes;
    $vars['attributes'] = isset($element['attributes']) ? $element['attributes'] : array();
    $vars['legend']       = isset($element['legend']) ? $element['legend'] : '';
    $container_type = isset($element['type']) ? $element['type'] : 'div';

    switch($container_type){
      case 'row':
        $vars['content'] = $this->render_columns($element['columns'], $readonly, $is_clone);
        $rendered_container =  kb::view('common/row_div', $vars);
        break;
      case 'fieldset':
      case 'div':
        $children = isset($element['children']) ? $element['children'] : $element;
        $vars['content'] = $this->render_children($children, $readonly, $is_clone);
        $rendered_container =  kb::view('container_div', $vars);
        break;
      default:
        die('ct(kb_form):'.$container_type);
        break;
    }
    return $rendered_container;
    /*
    if(isset($element['children'])){
      foreach($element['children'] as $c_key => $c_field){
        $vars['content'] .= $this->render_element($c_key, $c_field, $readonly);
      }

      $container_type = isset($element['type']) ? $element['type'] : 'fieldset';
      switch($container_type){
        case 'row':
          die('row');
          break;
        case 'div':
          $vars['legend']       = isset($element['legend']) && !empty($element['legend']) ? $element['legend'] : NULL;
          $rendered_container =  kb::view('container_div', $vars);
          break;
        case 'fieldset':
        default:
          $vars['legend']       = isset($element['legend']) ? $element['legend'] : '';
          $vars['sub_legend']   = isset($element['sub_legend']) ? $element['sub_legend'] : '';
          $rendered_container =  kb::view('container_fieldset', $vars);
        break;
      }
    }else{
      // just children in a DIV
      foreach($element as $c_key => $c_field){
        $vars['content'] .= $this->render_element($c_key, $c_field, $readonly);
      }
      $rendered_container =  kb::view('container_div', $vars);
    }
    return $rendered_container;
     * 
     */
  }
  function render_children($children = array(), $readonly = FALSE, $is_clone = FALSE){
    $rendered_children = array();
    foreach($children as $key => $element){
      $rendered_children[] = $this->render_element($key, $element, $readonly, $is_clone);
    }
    return implode("\r\n", $rendered_children);
  }

  public function render_columns($columns = array(), $readonly = FALSE, $is_clone = FALSE){
    $rendered_columns = array();
    foreach($columns as $key => $column){
      $vars = array();
      $vars['content'] = '';
      $vars['id']     = $key;
      $classes = isset($column['classes']) ? $column['classes'] : '';
      $classes = !empty($classes) ? (is_array($classes) ? implode(' ', $classes) : $classes) : '';
      $vars['classes'] = $readonly ? $classes . ' readonly' : $classes;
      $vars['classes'] .= ' col-' . (isset($column['col-style']) ? ($column['col-style'] . '-') : 'xs-') . $column['width'];
      $vars['classes'] .= isset($column['col-offset']) ? ' col-offset-' .$column['col-offset']  : '';
      $vars['classes'] .= isset($column['col-push']) ? ' col-push-' .$column['col-push']  : '';

      $vars['attributes'] = isset($column['attributes']) ? $column['attributes'] : array();
      $vars['legend']       = isset($column['legend']) ? $column['legend'] : '';
      $vars['content'] = $this->render_column_children($column['children'], $readonly, $is_clone);
      $rendered_columns[] = kb::view('common/col_div', $vars);
    }
    return implode("\r\n", $rendered_columns);
  }

  function render_column_children($children = array(), $readonly = FALSE, $is_clone = FALSE){
    $rendered_children = array();
    foreach($children as $key => $element){
      $rendered_children[] = $this->render_element($key, $element, $readonly, $is_clone);
    }
    return implode("\r\n", $rendered_children);
  }


  public function render_field($key = null, $element = null, $readonly = FALSE, $is_clone = FALSE){
    $element->settings['real_id'] = $element->settings['id'];
    if($this->sub_form){
      if($this->sub_form_new){
        $key = 'new';
      }else{
        $key = 'existing';
      }
      $element->attributes['data-related-one-to-many-field-id'] = 'data-related-one-to-many-field-id="' . $element->settings['id'] . '"';
      $element->settings['id'] = $this->form_id . '[' . $key . '][' . $this->sub_form_id . '][' . $element->settings['id'] . ']';
    }

		$element->set_readonly($readonly);

    if($is_clone){
      $element->set_required(FALSE);
      if(isset($element->settings['rules'])){
        if(preg_match('`required`i', $element->settings['rules'])){
          $element->add_class('kb-to-be-required');
          $element->settings['rules'] = preg_replace('`required`', '', $element->settings['rules']);
        }
      }
    }

    $vars             = $element->settings;
		$vars['classes']	= $element->classes();
    $v = $element->get_value();
    $empty = is_string($v) ? (trim($v) == '') : (is_array($v) ? empty($v) : empty($v));
    if($empty){
      $element->classes['initial-value'] = 'no-value-onload';
    }else{
      $element->classes['initial-value'] = 'has-value-onload';
    }

    $vars['content']  = $element->render();
    $rendered_field =  kb::view('field_wrapper', $vars);
    return $rendered_field;
  }

  public function render_buttons(){
    $did_submit = 0;
    $did_reset = 0;

    $buttons = array();
    foreach($this->buttons as $label=>$type){
      $vars = array('label'=>$label, 'form_id'=>$this->form_id, 'index'=> $this->button_index++);
      $value = is_array($type) ? $type['value'] : $type;
      switch($value){
        case 'reset':
          $vars['confirm'] = '';
          $vars['type'] = 'reset';
          $vars['name'] = 'reset';
          $did_reset++;
          if($did_reset > 1){
            $vars['name'] .= '-' . $did_reset;
          }
          break;
        case 'cancel':
        case 'submit':
        default:
          $vars['type'] = 'submit';
          $vars['name'] = 'kb-form-submit';
          $vars['value'] = $value;
          $vars['confirm'] = (is_array($type) && isset($type['confirm'])) ? $type['confirm'] : '';
          $did_submit++;
          break;
      }
      $buttons[] = kb::view('form_button', $vars);
    }
    $vars['buttons_content'] = implode(' ', $buttons);
    $vars['form_id'] = $this->form_id;
    return kb::view('buttons_fieldset', $vars);
  }


  public function render_form_close(){
    $form_close_str = '';
    $form_close_str .= '</form>';
    return $form_close_str;
  }

  public function get_classes(){
    return implode(' ', $this->class);
  }

  public function add_class($class = null){
    if(is_array($class)){
      $this->class = array_merge($this->class, $class);
    }else{
      $this->class[] = $class;
    }
  }


  public function get_file_contents($key = null){
		$values = $this->values();
    return file_get_contents(kb::session_data($values[$key]['path']));
  }


  public function set_value($key = NULL, $values = NULL, $db_format = true){
    if( isset($this->fields[$key])){
      $this->fields[$key]->set_value($values, $db_format);
    }
  }

	public function get_value($id = null, $db_format = TRUE){
		$val = null;
		if(!empty($id) && isset($this->fields[$id])){
			$val = $this->fields[$id]->get_value($db_format);
		}
		return $val;
	}

	public function values_by_model(){
		$values = $this->values();
		$by_model = array();
		foreach($values as $key=>$value){
			$model = isset($this->field_definitions[$key]['model']) && !empty($this->field_definitions[$key]['model']) ? $this->field_definitions[$key]['model'] : '_none';
      if('_text' != $model){
        if( !isset($by_model[$model])){
          $by_model[$model] = array();
        }
        $by_model[$model][$key] = $value;
      }
		}


		return $by_model;
	}
  
  public function settings($rows){
    $values = array();
    foreach($rows as $r){
      $values[$r['n']] = $r['v'];
    }
    var_export($values);
    $this->values($values);
  }

  public function values($values = null, $db_format = true){
    if(!is_null($values)){
      $this->set_values($values, $db_format);
      $values = $this->get_values($db_format);
    }else{
      $values = $this->get_values($db_format);
    }
    return $values;
  }

  public function set_values($values = array(), $db_format = true){
    foreach($values as $key=>$value){
      if( isset($this->fields[$key])){
        $this->fields[$key]->set_value($value, $db_format);
      }
    }
  }

  public function get_values($db_format = true){
    $values = array();
    foreach($this->fields as $key => $field){
        $values[$key] = $field->get_value($db_format);
      }
    return $values;
  }


  /*
    * call the forms' render method and return its return
    * @access public
    */

  public function __toString() {
      return $this->render();
  }

  public static function autoload($class) {
    $widget_directory = empty(self::$form_widget_directory) ? 'v1' : self::$form_widget_directory;
    $dir = dirname(__FILE__) . '/../forms/' . $widget_directory . '/models';

    $check_path[] = strtolower($dir . DIRECTORY_SEPARATOR   . $class . '.php');
    $check_path[] = strtolower($dir . DIRECTORY_SEPARATOR   . 'models' . DIRECTORY_SEPARATOR . $class . '.php');
    $check_path[] =strtolower(APPPATH . 'models' . DIRECTORY_SEPARATOR . 'checklists' . DIRECTORY_SEPARATOR . $class . '.php');
    
    foreach($check_path as $path){
      if (is_readable($path)) {
        require_once($path);
        return TRUE;
      }
    }
    return FALSE;
  }

  public static function _load(){
    return TRUE;
  }
  
  
	/**
	 * __get
	 *
	 * Allows models to access CI's loaded classes using the same
	 * syntax as controllers.
	 *
	 * @param	string
	 * @access private
	 */
	function __get($key)
	{
		$CI =& get_instance();
		return $CI->$key;
	}
}
