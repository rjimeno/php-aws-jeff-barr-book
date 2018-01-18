<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title><?php echo $output_title ?></title>
</head>
<body>
<h1><?php echo $output_title ?></h1>
<p><?php echo $output_message ?></p>
<?php foreach($chartImages as $image): ?>
    <img src="<?php echo $image ?>"/>
<?php endforeach ?>
</body>
</html>