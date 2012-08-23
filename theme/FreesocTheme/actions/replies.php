<?php
/*
 *  Name: Remote Profile
 *  Type: noticelist
 */
	$this->box('header');
?>
<h2 id="content-title"><?php $this->the_title(); ?></h2>
<div id="content"><?php $this->content('noticelist'); ?></div>
<?php
    $this->box('aside');
    $this->box('footer');
?>
