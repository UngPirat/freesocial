<?php

class NoticeWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $notice;
    protected $avatarSize = AVATAR_STREAM_SIZE;

    static function run($args=null) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
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

        $this->profile = $this->notice->getProfile();    // $this->notice->_profile is a protected value
    }

    function show() {
        if (!$this->notice->inScope($this->scoped)) {
            return false;
        }
		if (!empty($this->notice->repeat_of)) {
			$this->repeated = Notice::staticGet('id', $this->notice->repeat_of);
		}
        $this->out->elementStart('article', array('id'=>'notice-'.$this->notice->id, 'class'=>'notice'));
        $this->the_vcard();
        $this->the_content();
        $this->the_metadata();
        $this->the_controls();
        $this->out->elementEnd('article');
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
    function get_permalink() {
        return $this->notice->url ? $this->notice->url : $this->notice->uri;
    }
    function get_conversation_url() {
        return common_local_url('conversation', array('id'=>$this->notice->conversation)).'#notice-'.$this->get_id();
    }
    function get_rendered_content() {
		$notice = (isset($this->repeated) ? $this->repeated : $this->notice);
        return $notice->rendered
                ? $notice->rendered
                : common_render_content($notice->content, $notice);
    }
    function get_webfinger() {
        return $this->profile->nickname . '@' . parse_url($this->profile->profileurl, PHP_URL_HOST);
    }
    function the_content() {
        $this->out->elementStart('span', 'notice-content'.($this->repeated ? ' repeat' : ''));
        $this->out->raw($this->get_rendered_content());
        $this->out->elementEnd('span');
    }
	function the_controls() {
//		$this->out->elementStart('aside', 'controls');
//		$this->out->elementEnd('aside');
	}
    function the_metadata() {
        $this->out->elementStart('footer', 'metadata');
        $this->the_author();
        $this->the_timestamp();
		$this->the_repeat() or $this->the_source();
        $this->out->elementEnd('footer');
    }
	function the_repeat() {
		if (empty($this->repeated)) {
			return false;
		}
		$profile = $this->repeated->getProfile();
        $this->out->elementStart('span', 'source repeat');
		$from = sprintf(_m('as repeat of %s'), $this->get_name($profile));
        $this->out->element('a', array('href'=>$this->repeated->uri, 'rel'=>'external'), $from);
        $this->out->elementEnd('span');
		return true;
	}
    function the_source() {
        $ns   = $this->notice->getSource();
        $name = empty($ns->name)
                    ? ($ns->code
                        ? _($ns->code)
                        : _m('SOURCE','web'))
                    : _($ns->name);
		$from = sprintf(_m('from %s'), $name);
        $this->out->elementStart('span', 'source device');
        if (!empty($ns->url)) {
            $this->out->element('a', array('href'=>$ns->url, 'rel'=>'external'), $from);
        } else {
            $this->out->text($from);
        }
        $this->out->elementEnd('span');
    }
    function the_author() {
		$profileurl = common_local_url('remoteprofile', array('id'=>$this->profile->id));
        $this->out->element('a', array('href'=>$profileurl, 'class'=>'author'), $this->get_name());
	}
    function the_timestamp() {
		$context = $this->notice->reply_to
					? _m('replied')
					: _m('posted');
		$this->out->elementStart('a', array('class'=>'timestamp', 'href'=>$this->get_conversation_url()));
		$this->out->element('span', 'context', $context);
        $this->out->element('time', array('pubdate'=>'pubdate', 'datetime'=>common_date_iso8601($this->notice->created)),
                                common_date_string($this->notice->created));
        $this->out->elementEnd('a');
    }
    function the_vcard() {
        $this->out->elementStart('span', 'vcard author');
        $this->out->elementStart('a', array('href'=>common_local_url('remoteprofile', array('id'=>$this->profile->id))));
        $this->out->element('img', array('src'=>$this->profile->avatarUrl($this->avatarSize), 'alt'=>'', 'class'=>'photo'));
		$this->out->element('span', 'fn', $this->get_name());
        $this->out->elementEnd('a');
        $this->out->element('a', array('href'=>$this->profile->profileurl, 'class' => 'url'), _m('Original profile'));
        $this->out->elementEnd('span');
    }
}

?>
