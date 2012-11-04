<?php

abstract class FormWidget extends ThemeForm {
    protected $title  = null;
    protected $class  = null;
    protected $method = 'post';
    protected $returnto = null;

	protected $rights = null;

    protected $widgetClass;
    protected $widgetTag = 'section';
    protected $widgetId;

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

    abstract function the_submit();

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
		echo 'widgetClass: '.$this->widgetClass;
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
