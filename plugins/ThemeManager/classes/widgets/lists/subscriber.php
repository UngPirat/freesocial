<?php

class SubscriberListWidget extends ProfileListWidget {
    protected $itemClass   = 'subscriber';
    protected $widgetClass = 'subscribers mini-list';

    static function run($args=null) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

	function initialize() {
		parent::initialize();

		$this->title = _m('Followers');
	}

    function get_list() {
        return $this->profile->getSubscribers(0, 0+$this->num);
    }
}
