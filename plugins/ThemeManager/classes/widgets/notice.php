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
        return true;
    }

    protected function initialize() {
        $this->profile = $this->notice->getProfile();    // $this->notice->_profile is a protected value
    }

    function show() {
        // make this use the htmloutputter
?>
            <span class="vcard">
                <a href="<?php $this->the_profileurl(); ?>" class="url" title="<?php $this->the_nickname(); ?>">
                    <?php $this->the_avatar(); ?>
                    <span class="fn"><?php $this->the_name(); ?></span>
                </a>
            </span>
            <div class="note"><?php $this->the_content(); ?></div>
            <span class="metadata">
                <a href="<?php $this->the_url(); ?>" class="timestamp" rel="bookmark">
                    <?php $this->the_timestamp(); ?>
                </a>
                <?php $this->the_source(); ?>
                <?php $this->the_context(); ?>
            </span>
<?php
    }

    function the_avatar() {
        $this->out->element('img', array('src'    => $this->profile->avatarUrl($this->avatarSize),
                                         'class'  => 'avatar',
                                         'alt'    => $this->get_name(),
                                   ));
        $this->out->flush();
    }

    function get_name() {
        return $this->profile->fullname
                ? $this->profile->fullname
                : $this->profile->nickname;
    }

    function the_content() {
        echo $this->notice->rendered
                ? $this->notice->rendered
                : $this->notice->content;
    }
    function the_context() {
        if (!$this->notice->hasConversation()) {
            return;
        }
        echo 'in context';
    }
    function the_id() {
        echo htmlspecialchars($this->notice->id);
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
        $ns = $this->notice->getSource();
        $this->out->elementStart('span', 'device');
        // TRANS: Followed by notice source.
        $this->out->text(_('from') . ' ');
        if (!empty($ns->url)) {
            $this->out->element('a', array('href' => $ns->url,
                                           'rel'  => 'external'),
                                $ns->name);
        } else {
            $this->out->text($ns->name);
        }
        $this->out->elementEnd('span');
        $this->out->flush();
    }
    function the_timestamp() {
        $this->out->element('abbr', array('title' => common_date_iso8601($this->notice->created)),
                                common_date_string($this->notice->created));
        $this->out->flush();
    }
    function the_url() {
        echo htmlspecialchars($this->notice->url ? $this->notice->url : $this->notice->uri);
    }
}

?>
