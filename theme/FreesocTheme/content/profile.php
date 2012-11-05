<?php
	$this->out->element('p', null, $this->action->profile->getFancyName() . ' is using ' . common_config('site', 'name'). '. Feel free to browse around in this social atmosphere. Start with the menu you see above -^');
	$this->out->element('p', null, common_config('site', 'name'). ' runs the protocol OStatus to build a social network with other federated social sites, like identi.ca and joindiaspora.com.');
