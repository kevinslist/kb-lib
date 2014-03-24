<!DOCTYPE html>
<html>
<head>
<title><?=kb::ci()->page_title();?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?=$css?>
<script type="text/javascript">
  var app_home = "<?= site_url() ?>";
  var ajax_home = app_home + "ajax/";
  var kb_csrf_hash = "<?php print kb::ci()->security->get_csrf_hash(); ?>";
  var kb_csrf_name = "<?php print kb::ci()->security->get_csrf_token_name(); ?>";
</script>
</head>
<body data-site-url="<?= site_url() ?>">
<div id="sic-wrapper">
<?=$content?>
</div>
<?=$js?>
</body>
</html>