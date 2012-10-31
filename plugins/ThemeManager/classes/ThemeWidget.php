<?php

abstract class ThemeWidget extends ThemeExtension {
    protected $out;
	protected $ajax = false;

    protected $widgetClass;
    protected $widgetTag = 'section';
    protected $widgetId;

    abstract static function run(array $args=array());

    protected function validate() {    // make sure we don't have garbage
        if (!empty($this->out) && !is_a($this->out, 'HTMLOutputter')) {
            return false;
        }

        return parent::validate();
    }

    protected function initialize() {
		parent::initialize();

        $this->getOut();

/*		if ($this->ajax) {
			$this->widgetTag = null;
		}*/
    }

	function getOut() {
        if (empty($this->out)) {
		    $this->out = ThemeManager::getOut();
        }
		return $this->out;
	}
}
