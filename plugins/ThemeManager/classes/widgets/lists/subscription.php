<?php

class SubscriptionListWidget extends ProfileListWidget {
	protected $action    = 'subscriptions';

    protected $itemClass = 'subscription';
	protected $itemTag   = null;

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    function get_list() {
        return $this->item->getSubscriptions(0, 0+$this->num);
    }

	function get_count() {
		return $this->item->subscriptionCount();
	}
}
