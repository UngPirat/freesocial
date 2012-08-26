<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
       "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php $this->the_lang(); ?>" lang="<?php $this->the_lang(); ?>">
<head>
	<title><?php
		$this->the_title();
	    if (isset($args['page']) && $args['page'] >= 2) {
	        echo ' - ' . sprintf( _m('Page %s'), htmlspecialchars($args['page']));
	    }
        echo ' | ';
		$this->the_siteinfo('name');
?></title>
<?php $this->head(); ?>
</head>
<body>
<div id="wrapper">
<div id="header">
<?php $this->box('site-title'); ?>
	<div id="login">
<?php
    try {
        $this->widget('Vcard', array('profile'=>$this->profile,'avatarSize'=>48));
    } catch (Exception $e) {
?>
		<p>You are not logged in!</p>
		<p>Do you wish to <a href="<?php echo common_local_url('login'); ?>">log in</a> or <a href="<?php echo common_local_url('register'); ?>">register an account</a>?</p>
<?php
    }
?>
	</div>
<?php $this->box('topmenu'); ?>
</div>
