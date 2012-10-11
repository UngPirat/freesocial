<?php

class ProfileactionsWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $item;

    protected $widgetClass = '';
    protected $widgetTag   = 'section';

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    protected function validate() {
        if (!is_a($this->item, 'Profile')) {
            return false;
        }
        return parent::validate();
    }

    // only gets called for with in users
    function get_actions() {
        $actions = array();
		if ($this->item->isGroup()) {
			return $actions;
		}
        if ($this->scoped->isSubscribed($this->item)) {
            $actions['subscribe'] = new UnsubscribeForm($this->out, $this->item);
        } else if ($this->scoped->hasPendingSubscription($this->item)) {
            $actions['subscribe'] = new CancelSubscriptionForm($this->out, $this->item);
        } else {
            $actions['subscribe'] = new SubscribeForm($this->out, $this->item);
        }
        return $actions;
    }

    function show() {
        if (Event::handle('StartProfilePageActionsSection', array($this->out, $this->item))) {
            $this->out->elementStart($this->widgetTag, "actions {$this->widgetClass}");
            $this->out->element('h3', null, _m('Profile actions'));
            $this->out->elementStart('ul');
            if (Event::handle('StartProfilePageActionsElements', array($this->out, $this->item))) {
                if (empty($this->scoped)) { // not logged in
                    if (Event::handle('StartProfileRemoteSubscribe', array($this->out, $this->item))) {
                        Event::handle('EndProfileRemoteSubscribe', array($this->out, $this->item));
                    }
                } else {
                    foreach ($this->get_actions() as $action=>$data) {
                        $this->out->elementStart('li', "entity_{$action}");
                        if (is_a($data, 'Form')) {
                            $data->show();
                        } elseif (is_array($data)) {
                            $this->out->element($data['element'], $data['args'], $data['content']);
                        }
                        $this->out->elementEnd('li');
                    }
                }
                Event::handle('EndProfilePageActionsElements', array($this->out, $this->item));
            }
            $this->out->elementEnd('ul');
            $this->out->elementEnd($this->widgetTag);
            Event::handle('EndProfilePageActionsSection', array($this->out, $this->item));
        }
    }
}

?>
