<?php

class SubscriptionListWidget extends ProfileListWidget {
    protected $itemClass   = 'subscribed';
    protected $widgetClass = 'subscriptions mini-list';

    static function run($args=null) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

	function initialize() {
		parent::initialize();

		$this->title = _m('Following');
	}

    function get_list() {
        return $this->profile->getSubscriptions(0, 0+$this->num);
    }
}
