<div id="aside">
<?php

	if (common_logged_in() && $this->get_template() == 'profile') :
?>
		<h2><?php echo _m('Profile data'); ?></h2>
		<?php $this->widget('Profile', array('profile'=>$this->action->profile)); ?>
<?php
	elseif (common_logged_in() && $this->get_template() == 'group') :
?>
		The group's related data.
<?php
	else :
?>
		Not-logged-in stuff.
<?php
	endif;

?>
</div>
