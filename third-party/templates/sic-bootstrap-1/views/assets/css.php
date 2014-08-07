<?php foreach($css_files as $c):
  $css_url = preg_match('`^//`', $c) ? $c : site_url($c);
?><link href="<?php echo $css_url;?>" rel="stylesheet">
<?php endforeach; ?>

