<?php
  $css_array = bsg::config('bsg_app_css');
  if($is_ie){
    $css_array = array_merge($css_array, bsg::config('bsg_app_css_ie'));
  }
  if($bad_ie){
    $css_array = array_merge($css_array, bsg::config('bsg_app_css_ie_8'));
  }
  $min_css_string = $min_base_path . implode(',', $css_array);
?><link type="text/css" rel="stylesheet" href="<?=$min_css_string?>">