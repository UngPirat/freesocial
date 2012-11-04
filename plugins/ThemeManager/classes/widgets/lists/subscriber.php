<?php

class SubscriberListWidget extends ProfileListWidget {
    protected $itemClass = 'subscriber';
	protected $itemTag   = null;

	protected $action = 'subscribers';

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    function get_list() {
        return $this->item->getSubscribers(0, 0+$this->num);
    }

	function get_count() {
		return $this->item->subscriberCount();
	}
}
