<?php

class DeleteForm extends ThemeForm {
	protected $widgetClass = 'delete';

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    function initialize() {
        parent::initialize();

        $this->attributes['action'] = common_local_url('delete');
    }

    function the_elements() {
        $els = new FormElements($this->out);
		if (is_a($this->item, 'Notice')) {
            $legend = _m('Delete notice');
		} else {
            $legend = _m('Delete');
		}
        $els->legend($legend);
		
        return $els;
    }
}

?>
