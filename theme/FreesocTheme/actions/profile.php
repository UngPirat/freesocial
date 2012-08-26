<?php
/*
 *  Name: Remote Profile
 */
	$this->box('header');
?>
<div id="content">
<?php
	if (is_a($this->action, 'ShowstreamAction')) {
		$this->content('profile');
	} else {
		$this->content('noticelist');
	}
?>
</div>
<?php
    $this->box('aside');
    $this->box('footer');
?>
