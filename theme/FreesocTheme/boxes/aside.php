<?php

	if (common_logged_in()) :
		$this->widgets(array('DefaultProfileBlock'=>array($this->action)));
	else :
?>
		<p>You are not logged in!</p>
<?php
	endif;

?>
