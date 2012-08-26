<?php
	$loop = $this->loop($this->action->notice);

	try {
		$pages = $loop->get_paging($this->action->args['page']);
	} catch (Exception $e) {
        $pages = array();
	}

	$this->pagination($pages);
?>
<ul class="noticelist">

<?php while($loop->next()) : ?>
	<li id="notice-<?php $loop->the_id(); ?>" class="notice">
<?php
        // initiates a NoticeWidget that renders the notice
        $this->widgets(array('NoticeWidget'=>array('notice'=>$loop->current())));
?>
	</li>
<?php endwhile; ?>
</ul>
<?php $this->pagination($pages); ?>
