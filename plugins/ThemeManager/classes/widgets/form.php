<?php

abstract class FormWidget extends ThemeWidget {
    protected $title  = null;
    protected $class  = null;
    protected $method = 'post';
    protected $returnto = null;

    protected $widgetClass;
    protected $widgetTag = 'section';
    protected $widgetId;

    protected function validate() {
		if (!is_null($this->returnto) && is_a($this->returnto, 'Action')) {
			$this->returnto = $this->returnto->trimmed('action');	// get action name
		}

        return parent::validate();
    }

	protected function initialize() {
		parent::initialize();

		$this->attributes = array();
		if (common_config('attachments', 'uploads')) {
			$this->attributes['enctype'] = 'multipart/form-data';
		}
		foreach (array('action', 'class', 'method') as $attr) :
			if (!empty($this->$attr)) {
				$this->attributes[$attr] = $this->$attr;
			}
		endforeach;
	}

    abstract function the_submit();

    function show() {
		$args = array('class'=>"form widget {$this->widgetClass}");
		if (!empty($this->widgetId)) {
			$args['id'] = $this->widgetId;
		}

        $this->widgetTag && $this->out->elementStart($this->widgetTag, $args);
        if (!empty($this->title)) {
            $this->out->element('h3', 'widget-title', $this->title);
        }
        $this->the_form();
        $this->widgetTag && $this->out->elementEnd($this->widgetTag);
    }

    function the_form() {
		$this->out->elementStart('form', $this->attributes);
        $this->the_token();

		$this->out->elementStart('fieldset');
		$this->the_elements();
		$this->out->elementEnd('fieldset');
		
		$this->the_submit();
		$this->out->elementEnd('form');
	}

	function the_token() {
		$this->out->hidden('token', common_session_token());
	}
}

class FormElements {
	public $out;

	function __construct($out) {
		if (!is_a($out, 'HTMLOutputter')) {
			throw new Exception('Invalid output object');
		}
		
		$this->out = $out;
	}

	function legend($text, $args=array()) {
		$this->out->element('legend', $args, $text);
	}
	function textarea($name, $args, $text) {
		$args = $this->make_arg_to_array($args);
		$args['name'] = $name;
		$this->out->element('textarea', $args, $text);
	}
	function hidden($name, $value) {
		$this->out->hidden($name, $value);
	}
	function span($class, $value) {
		$this->out->element('span', $class, $value);
	}
	function input($name, $args, $value) {
		$args = $this->make_arg_to_array($args, 'type');
		$args['name'] = $name;
		$args['value'] = $value;
		$this->out->element('input', $args);
	}

	function make_arg_to_array($args, $defaultkey='class') {
		if (empty($args)) {
			$args = array();
		} elseif (!is_array($args)) {
			$args = array($defaultkey=>$args);
		}
		return $args;
	}
}

?>
