<ul>
<?php
	$loop = $this->loop($this->action->notice, 'notice');
common_debug('THEMEMANAGER created loop');
	while($loop->next()) :	// do while, to avoid skipping first element?
?>
	<li id="notice-<?php $loop->the_id(); ?>" class="notice">
<?php
        // initiates a NoticeWidget that renders the notice
        $this->widgets(array('NoticeWidget'=>array('notice'=>$loop->current())));
?>
	</li>
<?php endwhile; ?>
</ul>
