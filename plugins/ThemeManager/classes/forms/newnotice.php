<?php

class NewnoticeForm extends ThemeForm {
    protected $content   = null;
    protected $returnto  = null;
    protected $inreplyto = null;
    protected $private   = null;
    protected $charlimit = null;

	protected $class = 'newnotice';

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

	function validate() {
		if (!empty($this->inreplyto)) {
			$this->class      .= ' reply';
			$this->widgetClass = 'reply';
		}

		return parent::validate();
	}

    function initialize() {
        parent::initialize();

        $this->attributes['action'] = common_local_url('newnotice');

        if (empty($this->charlimit)) {
            $this->charlimit = Notice::maxContent();
        }
    }

    function the_elements() {
        $els = new FormElements($this->out);
        
		$legend = _m('Send a notice');
		$txtId  = 'newnotice';
		
		if (!empty($this->inreplyto)) {
			$parent = Notice::staticGet('id', $this->inreplyto);
            $legend = sprintf(_m('Reply to %s'), $parent->getProfile()->getBestName());
			$txtId  = 'inreplyto-'.$this->inreplyto;
		}
		$attId = "$txtId-attach";
		$irtId = "$txtId-inreplyto";
		$retId = "$txtId-returnto";
		$sbmId = "$txtId-submit";

        $els->legend($legend);
		
        $els->hidden('token', common_session_token());
        $els->textarea('status_textarea', array('class'=>'notice_data-text', 'cols'=>35, 'rows'=>4, 'id'=>$txtId), $this->content);
        $els->span('count', $this->charlimit);

        $els->submit($sbmId, 'submit', (empty($this->inreplyto) ? _m('BUTTON', 'Send') : _m('BUTTON', 'Reply')));

        if (common_config('attachments', 'uploads')) {
            $this->out->elementStart('div', 'attachments');
            $els->hidden('MAX_FILE_SIZE', common_config('attachments', 'file_quota'));
            $els->file('attach[]', array('id'=>$attId, 'multiple'=>'multiple'), _m('Attach a file.'));
            $this->out->elementEnd('div');
        }
        if (!empty($this->returnto)) {
            $els->hidden('returnto', $this->returnto, $retId);
        }
        $els->hidden('inreplyto', $this->inreplyto, $irtId);
        return $els;
    }
}

?>
