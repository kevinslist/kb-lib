<?php


/**
 * Description of kb_form_widget
 *
 * @author kboydstu
 */
class kb_form_widget_select extends kb_form_widget{
  static $did_select    = FALSE;
  var $multiple         = FALSE;
  var $bsg_user_lookup  = FALSE;

  public function __construct($settings = array()){
    if(!isset($settings['initial']) || FALSE !== $settings['initial']){
      $settings['initial'] = empty($settings['initial']) ? 'Select One' : $settings['initial'];
    }
    if(!isset($settings['classes'])){
      $settings['classes'] = '';
    }
    if(isset($settings['multiple']) && FALSE !== $settings['multiple']){
      $settings['initial'] = FALSE;
      $this->multiple = TRUE;
    }
    if(isset($settings['bsg-user-lookup']) && TRUE === $settings['bsg-user-lookup']){
      $settings['classes'] = $settings['classes'] . ' bsg-user-lookup';
      $this->bsg_user_lookup = TRUE;
    }
    parent::__construct($settings);

  }

  public function set_value_form($value){
    if($this->multiple){
      $values = is_array($value) ? $value : array();
      $this->form_value = $values;
    }else{
      $this->form_value = $value;
    }
    $this->_set_value_db($this->form_value);


  }

	public function set_options($options = array()){
		$this->settings['options'] = $options;
	}


  public function render_input(){    
		if(!$this->get_readonly()){      
			// normal select box
			kb::add_js('bsg-form/bsg-form-widgets/bsg.select.js');
			parent::render_input();

      if($this->multiple){
        $content = kb::view('field_select_multi', $this->settings);
      }else{
        $content = kb::view('field_select', $this->settings);
      }
		}else{                  
      parent::render_input();
      if (!$this->multiple) {
        $fake_settings1 = $this->settings;
        $fake_settings1['widget'] = 'hidden';
        $content = kb::view('field_input', $fake_settings1);
      
        $fake_settings2 = $this->settings;
        $fake_settings2['id'] = $fake_settings2['id'] . '-readonly';
        $fake_settings2['widget'] = 'text';
        if($this->bsg_user_lookup){
          $fake_settings2['form_value'] = $this->settings['form_value'];
        }else{
          $fake_settings2['form_value'] = $this->settings['form_value'];
          $fake_settings2['form_value_readonly'] = isset($this->settings['options'][$this->settings['form_value']]) ? $this->settings['options'][$this->settings['form_value']] : '';
          $content .= kb::view('field_input_select_readonly', $fake_settings2);
        }      
      } else {       
        $text = "";
        if (isset($this->settings['form_value']) && !empty($this->settings['form_value'])) {          
              $text = "<ul>";
              foreach($this->settings['form_value'] as $val) {
                $text .= "<li>{$this->settings['options'][$val]}</li>";
              }          
              $text .= "</ul>";
        }
        $c['content'] = $text;
        $c['attributes'] = array('width'=>'780px');
        $content = kb::view('container_div', $c);
        //}
      }
		}

    return $content;
  }

  static function render_option($value, $label, $current_value){
    $option = '';

    if( is_array($current_value) ){
      if(in_array($value, $current_value, TRUE)){ //$strict
        self::$did_select = true;
        $selected = ' selected="selected" ';
      }else{
        $selected = '';
      }
    }else{
      $selected = '';
			if(!self::$did_select){
				if($value == $current_value && strlen($value) == strlen($current_value)){
					self::$did_select = true;
					$selected = ' selected="selected" ';
				}
      }

      $vars = array(
          'value'     =>$value,
          'label'     =>$label,
          'selected'  =>$selected,
      );
    }
    $vars = array(
        'value'     =>$value,
        'label'     =>$label,
        'selected'  =>$selected,
    );
    $option = kb::view('field_select_option', $vars);
    return $option;
  }

  public function attributes(){
    $attributes = parent::attributes();
    $attributes[] = 'size="1"';

    return array_unique($attributes);
  }


}