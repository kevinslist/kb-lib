<?php
  $min_js_string = $min_base_path . implode(',', bsg::config('bsg_app_js'));
  if($is_ie){
    $min_js_string .= ',' . implode(',', bsg::config('bsg_app_js_ie'));
  }
?><script type="text/javascript" src="<?=$min_js_string?>"></script>