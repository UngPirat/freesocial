<?php

	if (common_logged_in()) :
		$this->widget('DefaultProfileBlock', $this->action);
	else :
?>
		<p>You are not logged in!</p>
<?php
	endif;

?>
