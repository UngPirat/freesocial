<?php

class Ajax {
	static function getRedir($resource) {
		$matches = array();
		// example here is notice-1234
		if (!preg_match('/^(\w+)\-(\d+)$/', $resource, $matches)) {
			throw new Exception(_m('Bad resource description'));
		}
		// $matches[1] == 'notice', $matches[2] == '1234'
		return common_local_url($matches[1], array('id'=>$matches[2]));
	}
}
