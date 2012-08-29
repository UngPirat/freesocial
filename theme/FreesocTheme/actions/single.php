<?php
/*
 *  Name: Remote Profile
 */
	$this->box('header');
?>
<article id="content" class="single">
<?php
	switch ($this->action->args['action']) {
    case 'attachment':
		$this->content($this->action->args['action']);
		break;
	default:
		echo 'unhandled';
	}
?>
</article>
<?php
    $this->box('aside');
    $this->box('footer');
?>
