	<?php $this->widget('Grouplist', array('profile'=>$this->action->profile, 'out'=>$this->out, 'title'=>_m('Group memberships'))); ?>
	<?php $this->widget('Attachmentlist', array('profile'=>$this->action->profile, 'out'=>$this->out, 'title'=>_m('Latest attachments'))); ?>
	<div><h3 class="widget-title"><?php echo _m('Most popular'); ?></h3></div>
	<div><h3 class="widget-title"><?php echo _m('Friends, stalkers, following'); ?></h3></div>
