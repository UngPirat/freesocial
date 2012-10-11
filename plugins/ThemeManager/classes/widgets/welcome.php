<?php

class WelcomeWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $image = null;

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        return $widget->show();
    }

    function show() {
		if (!is_null($this->image)) {
			$this->out->elementStart('marquee');
			$this->out->element('img', array('src'=>$this->image,'alt'=>'Welcome to '.common_config('site', 'name').'!'));
			$this->out->elementEnd('marquee');
		}
    }
}
?>
