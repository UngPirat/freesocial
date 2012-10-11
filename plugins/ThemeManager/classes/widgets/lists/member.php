<?php

class MemberListWidget extends ProfileListWidget {
    protected $itemClass   = 'member';

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    function get_list() {
        return $this->item->getMembers(0, 0+$this->num);
    }

	function get_count() {
		return $this->item->memberCount();
	}
}
