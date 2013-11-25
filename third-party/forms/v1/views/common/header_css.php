<?php
  $css_array = bsg::config('bsg_app_css');
  if($is_ie){
    $css_array = array_merge($css_array, bsg::config('bsg_app_css_ie'));
  }
  if($bad_ie){
    $css_array = array_merge($css_array, bsg::config('bsg_app_css_ie_8'));
  }
?><?php foreach ($css_array as $c): ?>
<link type="text/css" rel="stylesheet" href="<?=$base_path . $c?>">
<?php endforeach; ?>