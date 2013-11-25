<?php
  $js_array = bsg::config('bsg_app_js');
  if($is_ie){
    $js_array = array_merge($js_array, bsg::config('bsg_app_js_ie'));
  }
?><?php foreach ($js_array as $j): ?>
<script type="text/javascript" src="<?=$base_path . $j?>"></script>
<?php endforeach; ?>