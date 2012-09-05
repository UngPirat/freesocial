<?php

abstract class ThemeWidget extends ThemeExtension {
    protected $out;

    protected $widgetClass;
    protected $widgetTag = 'section';
    protected $widgetId;

    abstract static function run($args=null);

    protected function validate() {    // make sure we don't have garbage
        if (!empty($this->out) && !is_a($this->out, 'HTMLOutputter')) {
            return false;
        }

        return parent::validate();
    }

    protected function initialize() {
        if (empty($this->out)) {
            $this->out = ThemeManager::getOut();
        }
    }
}
