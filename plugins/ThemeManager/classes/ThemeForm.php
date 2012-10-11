<?php

abstract class ThemeForm extends ThemeWidget {
    protected $title  = null;
    protected $class  = null;
    protected $method = 'post';
    protected $returnto = null;

    protected $rights = null;

    protected function validate() {
        if (!is_null($this->returnto) && is_a($this->returnto, 'Action')) {
            $this->returnto = $this->returnto->trimmed('action');    // get action name
        }

        return parent::validate();
    }

    protected function initialize() {
        parent::initialize();

        $this->attributes = array();
        if (common_config('attachments', 'uploads')) {
            $this->attributes['enctype'] = 'multipart/form-data';
        }
        foreach (array('action', 'class', 'method', 'id') as $attr) :
            if (!empty($this->$attr)) {
                $this->attributes[$attr] = $this->$attr;
            }
        endforeach;
    }

    function check_rights() {
        if (is_null($this->rights)) {
            return true;
        }
        if (!is_a($this->scoped, 'Profile')) {
            return false;
        }
        return $this->scoped->hasRight($this->right);
    }
    function the_rights() {
        $this->out->element('div', 'no-rights', 'You do not have permission to see this form.');
    }

    function show() {
        if (!$this->check_rights()) {
            $this->the_rights();
            return false;
        }

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
        return true;
    }

    function the_form() {
        $this->out->elementStart('form', $this->attributes);

        $this->out->elementStart('fieldset');
        $this->the_elements();
        $this->out->elementEnd('fieldset');
        
        $this->out->elementEnd('form');
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
    function span($class, $value) {
        $this->out->element('span', $class, $value);
    }
    function input($name, $args, $value, $label=null) {
        $args = $this->make_arg_to_array($args, 'type');
        $args['name'] = $name;
        $args['value'] = $value;
		if (!is_null($label)) {
			$this->out->element('label', array('for'=>$name), $label);
		}
        $this->out->element('input', $args);
    }
    function file($name, $args, $label) {
        $args = $this->make_arg_to_array($args, 'id');
		$args['name']   = $name;
        $args['type'] = 'file';
        $this->input($name, $args, null, $label);
    }
    function hidden($name, $value, $args=array()) {
        $args = $this->make_arg_to_array($args, 'id');
        $args['type'] = 'hidden';
        $this->input($name, $args, $value);
    }
	function password($name, $args, $label) {
        $args = $this->make_arg_to_array($args, 'id');
		$args['type'] = 'password';
		$this->input($name, $args, null, $label);
	}
    function submit($id, $args, $value) {
        $args = $this->make_arg_to_array($args);
        $args['id'] = $id;
        $args['type'] = 'submit';
        $this->input($id, $args, $value);
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
