<?php

class GroupListWidget extends ProfileListWidget {
    protected $itemClass = 'group';
	protected $itemTag = 'li';

	protected $action = 'usergroups';

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    function get_list() {
        return $this->item->getGroupProfiles(0, 0+$this->num, true);
    }

	function get_count() {
		return $this->item->groupCount();
	}
}
