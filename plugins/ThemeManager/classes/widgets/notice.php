<?php

class NoticeWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $notice;
    protected $avatarSize = AVATAR_STREAM_SIZE;

	protected $itemClass = 'notice';
	protected $itemTag = 'article';

    static function run($args=null) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        return $widget->show();
    }

    // always gets run on __construct, which is also called on ::run()
    protected function validate() {
        if (!is_a($this->notice, 'Notice')) {
            return false;
        }
        return parent::validate();
    }

    protected function initialize() {
        parent::initialize();

		if (!empty($this->notice->repeat_of)) {
			$this->repeated = Notice::staticGet('id', $this->notice->repeat_of);
			$this->repeater = $this->notice->getProfile();
			$this->profile  = $this->repeated->getProfile();	//refer it to the _original_ notice creator
		} else {
			$this->repeated = null;
			$this->repeater = null;
        	$this->profile  = $this->notice->getProfile();;
		}
    }

    function show() {
        if (!$this->notice->inScope($this->scoped)) {
            return false;
        }
		if ($score = Spam_score::staticGet('notice_id', $this->notice->id)) {
			$this->itemClass .= $score->is_spam ? ' spam' : '';
		}
        $this->itemTag && $this->out->elementStart($this->itemTag, array('id'=>'notice-'.$this->notice->id, 'class'=>$this->itemClass));
        $this->the_vcard();
        $this->the_content();
        $this->the_metadata();
        $this->the_actions();
        $this->itemTag && $this->out->elementEnd($this->itemTag);
		return true;
    }

    function get_id() {
        return $this->notice->id;
    }
    function get_name(Profile $profile=null) {
		$profile = (is_null($profile) ? $this->profile : $profile);
        return $profile->fullname
                ? $profile->fullname
                : $profile->nickname;
    }
	function get_profile_url(Profile $profile=null) {
		$profile = (is_null($profile) ? $this->profile : $profile);
		return common_local_url('remoteprofile', array('id'=>$profile->id));
	}
    function get_permalink() {
        return $this->notice->url ? $this->notice->url : $this->notice->uri;
    }
    function get_conversation_url() {
        return common_local_url('conversation', array('id'=>$this->notice->conversation)).'#notice-'.$this->get_id();
    }
	function get_context() {
		if (!empty($this->repeated)) {
			$context = _m('was repeated');
		} elseif (!empty($this->notice->reply_to)) {
			$context = _m('replied');
		} else {
			$context = _m('posted this');
		}
		return $context;
	}
	function get_recipients() {
		return $this->notice->getReplyProfiles();
	}
    function get_rendered_content() {
		$notice = !empty($this->repeated) ? $this->repeated : $this->notice;
        return $notice->rendered
                ? $notice->rendered
                : common_render_content($notice->content, $notice);
    }
    function the_content() {
        $this->out->elementStart('span', 'notice-content'.($this->repeated ? ' repeat' : ''));
        $this->out->raw($this->get_rendered_content());
        $this->out->elementEnd('span');
    }
	function the_actions() {
		try {
			NoticeactionsWidget::run(array('item'=>$this->notice, 'scoped'=>$this->scoped));
		} catch (Exception $e) {
		}
	}
    function the_metadata() {
        $this->out->elementStart('footer', 'metadata');
		// FIXME: this gets quite ugly for translations. Improve!
		$this->the_author();
        $this->the_timestamp();
		$this->the_related();
		$this->the_source();
        $this->out->elementEnd('footer');
    }
	function the_related() {
		if (!empty($this->repeated)) {
            $this->out->elementStart('span', 'source');
    		$this->out->element('span', 'context', _m('by'));
            $this->out->element('a', array('href'=>$this->get_profile_url($this->repeater), 'class'=>'repeater'), $this->get_name($this->repeater));
            $this->out->elementEnd('span');
		} elseif($recipients = $this->get_recipients()) {
            $this->out->elementStart('span', 'destination');
    		$this->out->element('span', 'context', _m('to'));
			foreach($recipients as $rcpt) {
            	$this->out->element('a', array('href'=>$this->get_profile_url($rcpt), 'class'=>'recipient'), $this->get_name($rcpt));
			}
            $this->out->elementEnd('span');
		}
		return true;
	}
    function the_source() {
		if (empty($this->repeated)) {
			return false;
		}
        $ns   = $this->notice->getSource();
        $name = empty($ns->name)
                    ? ($ns->code
                        ? _($ns->code)
                        : _m('SOURCE','web'))
                    : _($ns->name);
        $this->out->elementStart('span', 'source device');
		$this->out->element('span', 'descriptive', _m('using'));
        if (!empty($ns->url)) {
            $this->out->element('a', array('href'=>$ns->url, 'rel'=>'external'), $name);
        } else {
			$this->out->text($name);
        }
        $this->out->elementEnd('span');
    }
    function the_author() {	// original author if repeated!
        $this->out->element('a', array('href'=>$this->get_profile_url(), 'class'=>'author'), $this->get_name());
	}
    function the_timestamp() {
		$this->out->elementStart('a', array('class'=>'timestamp', 'href'=>$this->get_conversation_url()));
		$this->out->element('span', 'context', $this->get_context());
        $this->out->element('time', array('pubdate'=>'pubdate', 'datetime'=>common_date_iso8601($this->notice->created)),
                                common_date_string($this->notice->created));
        $this->out->elementEnd('a');
    }
    function the_vcard() {
        $this->out->elementStart('span', 'vcard author');
        $this->out->elementStart('a', array('href'=>$this->get_profile_url()));
        $this->out->element('img', array('src'=>$this->profile->avatarUrl($this->avatarSize), 'alt'=>'', 'class'=>'photo'));
		$this->out->element('span', 'fn', $this->get_name());
        $this->out->elementEnd('a');
        $this->out->element('a', array('href'=>$this->profile->profileurl, 'class' => 'url'), _m('Original profile'));
        $this->out->elementEnd('span');
    }
}

?>
