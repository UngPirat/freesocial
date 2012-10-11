<?php

class NoticeWidget extends ThemeWidget {
    // these values will be set by default or $args-supplied values
    protected $item;
    protected $avatarSize = Avatar::STREAM_SIZE;

    protected $itemClass = 'notice';
    protected $itemTag = 'article';

    static function run(array $args=array()) {
        if (Event::handle('StartRunNoticeWidget', array($args))) {
            $class = get_class();
            $widget = new $class($args);    // runs validate()
            $widget->show();
            Event::handle('EndRunNoticeWidget', array($widget));
        }
    }

    // always gets run on __construct, which is also called on ::run()
    protected function validate() {
        if (!is_a($this->item, 'Notice')) {
            return false;
        }
        return parent::validate();
    }

    protected function initialize() {
        parent::initialize();

        if (!empty($this->item->repeat_of)) {
            $this->repeated = Notice::staticGet('id', $this->item->repeat_of);
            $this->repeater = $this->item->getProfile();
            $this->profile  = $this->repeated->getProfile();    //refer it to the _original_ notice creator
        } else {
            $this->repeated = null;
            $this->repeater = null;
            $this->profile  = $this->item->getProfile();;
        }
    }

    function show() {
        if (!$this->item->inScope($this->scoped)) {
            return false;	// shouldn't this throw an exception?
        }
        if (!empty($this->item->repeat_of)) {
            $this->itemClass .= ' repeat';
        }
        if (class_exists('Spam_score') && $score = Spam_score::staticGet('notice_id', $this->item->id)) {
            $this->itemClass .= $score->is_spam ? ' spam' : '';
        }
        $this->the_item();
        return true;
    }

    function the_item() {
        $this->itemTag && $this->out->elementStart($this->itemTag, array('id'=>'notice-'.$this->get_notice_id(), 'class'=>$this->itemClass));
        $this->the_vcard();
        $this->the_content();
        if (common_config('attachments', 'show_thumbs')) {
            $this->the_attachments();
        }
        $this->the_metadata();
        $this->the_actions();
        $this->itemTag && $this->out->elementEnd($this->itemTag);
    }

    function get_notice() {
        if (!empty($this->repeated)) {
            return $this->repeated;
        }

        return $this->item;
    }
    function get_conversation_id() {
        return $this->get_notice()->conversation;
    }
    function get_conversation_url() {
        return common_local_url('conversation', array('id'=>$this->get_conversation_id())).'#notice-'.$this->get_notice_id();
    }
    function get_notice_id() {
        return $this->get_notice()->id;
    }
    function get_name(Profile $profile=null) {
        $profile = (is_null($profile) ? $this->profile : $profile);
        return $profile->fullname
                ? $profile->fullname
                : $profile->nickname;
    }
    function get_profile_url(Profile $profile=null) {
        $profile = (is_null($profile) ? $this->profile : $profile);
        return class_exists('RemoteProfileAction')
                ? common_local_url('remoteprofile', array('id'=>$profile->id))
                : $profile->profileurl;
    }
    function get_permalink() {
        return common_local_url('shownotice', array('notice'=>$this->item->id));
    }
    function get_verb() {
        if (!empty($this->repeated)) {
            $verb = _m('repeated');
        } elseif (!empty($this->item->reply_to)) {
            $verb = _m('replied');
        } else {
            $verb = _m('posted');
        }
        return $verb;
    }
    function get_recipients() {
        return $this->item->getMentionProfiles();
    }
    function get_rendered_content() {
        $notice = $this->get_notice();
        return $notice->rendered
                ? $notice->rendered
                : common_render_content($notice->content, $notice);
    }
    function the_attachments() {
		AttachmentListWidget::run(array(
									'notice'=>$this->get_notice(),
									'title'=>_m('Attachments'),
									'hideEmpty'=>true,
								));
    }
    function the_content() {
        $this->out->flush();    // PHP crashes (memory limit?) if we don't flush once in a while
        $this->out->elementStart('span', 'content');
        $this->out->raw($this->get_rendered_content());
        $this->out->elementEnd('span');
    }
    function the_actions() {
        try {
            NoticeactionsWidget::run(array('item'=>$this->get_notice(), 'scoped'=>$this->scoped));
        } catch (Exception $e) {
        }
    }
    function the_metadata() {
        $this->out->elementStart('footer', 'metadata');
//        $this->the_author();
        $this->the_verb();
        $this->the_timestamp();
        $this->the_context();
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
        $ns   = $this->item->getSource();
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
    function the_permalink() {
        $this->out->element('a', array('href'=>$this->get_permalink(), 'class'=>'permalink'), _m('Permalink'));
    }
    function the_verb() {
        $this->out->element('span', 'verb', $this->get_verb());
    }
    function the_author() {    // original author if repeated!
        $this->out->element('a', array('href'=>$this->get_profile_url(), 'class'=>'author'), $this->get_name());
    }
    function the_context() {
        if ($this->item->hasConversation()) {
            $this->out->element('a', array('href'=>$this->get_conversation_url(), 'class'=>'context'), _m('in context'));
        }
    }
    function the_timestamp() {
        $this->out->elementStart('a', array('href'=>$this->get_permalink(), 'class'=>'permalink timestamp', 'title'=>_m('Permalink')));
        $this->out->element('time', array('pubdate'=>'pubdate', 'datetime'=>common_date_iso8601($this->item->created)),
                                common_date_string($this->item->created));
        $this->out->elementEnd('a');
    }
    function the_vcard() {
        VcardWidget::run(array('item'=>$this->profile, 'avatarSize'=>$this->avatarSize, 'mini'=>true));
    }
}

?>
