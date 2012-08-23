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
        $this->the_vcard();
        $this->the_content();
        $this->the_metadata();
        $this->out->flush();
    }

    function get_name() {
        return $this->profile->fullname
                ? $this->profile->fullname
                : $this->profile->nickname;
    }
    function get_permalink() {
        return $this->notice->url ? $this->notice->url : $this->notice->uri;
    }
    function get_conversation_url() {
        return common_local_url('conversation', array('id'=>$this->notice->conversation));
    }
    function get_rendered_content() {
        return $this->notice->rendered
                ? $this->notice->rendered
                : common_render_content($this->notice->content, $this->notice);
    }
    function get_webfinger() {
        return $this->profile->nickname . '@' . parse_url($this->profile->profileurl, PHP_URL_HOST);
    }
    function the_content() {
        $this->out->elementStart('span', 'notice-content');
        $this->out->raw($this->get_rendered_content());
        $this->out->elementEnd('span');
    }
    function the_context_link() {
        if (!$this->notice->hasConversation()) {
            return;
        }
        $this->out->element('a', array('href'=>$this->get_conversation_url(), 'rel'=>'bookmark'), _m('in context'));
    }
    function the_id() {
        echo htmlspecialchars($this->notice->id);
    }
    function the_metadata() {
        $this->out->elementStart('span', 'metadata');
        $this->the_timestamp();
        $this->the_source();
        $this->the_context_link();
        $this->out->elementEnd('span');
    }
    function the_name() {
        echo htmlspecialchars($this->get_name());
    }
    function the_nickname() {
        echo htmlspecialchars($this->profile->nickname);
    }
    function the_profileurl() {
        echo htmlspecialchars($this->profile->profileurl);
    }
    function the_source() {
        $ns   = $this->notice->getSource();
        $name = empty($ns->name)
                    ? ($ns->code
                        ? _($ns->code)
                        : _m('SOURCE','web'))
                    : _($ns->name);
        $this->out->elementStart('span', 'source');
        // TRANS: Followed by notice source.
        $this->out->text(_m('from') . ' ');
        $this->out->elementStart('span', 'device');
        if (!empty($ns->url)) {
            $this->out->element('a', array('href' => $ns->url, 'rel'  => 'external'), $name);
        } else {
            $this->out->text($name);
        }
        $this->out->elementEnd('span');
        $this->out->elementEnd('span');
    }
    function the_timestamp() {
        $this->out->elementStart('span', 'timestamp');
        $this->out->elementStart('a', array('href'=>$this->get_conversation_url(), 'rel'=>'bookmark'));
        $this->out->element('span', 'descriptive', _m('Posted'));
        $this->out->element('abbr', array('class'=>'informative', 'title' => common_date_iso8601($this->notice->created)),
                                common_date_string($this->notice->created));
        $this->out->elementEnd('a');
        $this->out->elementEnd('span');
    }
    function the_vcard() {
        $this->out->elementStart('span', 'vcard');
        $this->out->element('img', array('src'    => $this->profile->avatarUrl($this->avatarSize),
                                         'class'  => 'photo',
                                         'alt'    => sprintf(_('Photo of %s'), $this->get_name()),
                                   ));
        $this->out->element('a', array('href'  => $this->profile->profileurl,
                                       'class' => 'url fn'),
                            $this->get_name());
        $this->out->elementEnd('span');
    }
}

?>
