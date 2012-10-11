<?php

class BookmarkWidget extends NoticeWidget {
    protected $itemClass = 'notice bookmark';
    protected $itemTag = 'article';

    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
		Event::handle('EndRunNoticeWidget', array($widget));
    }

	function get_verb() {
		return _m('bookmarked');
	}
    function the_content() {
		$this->out->flush();	// PHP crashes (memory limit?) if we don't flush once in a while
        $nb = Bookmark::getByNotice($this->item);

        if (empty($nb)) {
            common_log(LOG_ERR, 'No bookmark for notice '.$this->get_notice_id());
			parent::the_content();
            throw new ServerException(_m('Bookmark not found'));
            return;
        } else if (empty($nb->url)) {
            common_log(LOG_ERR, 'No url for bookmark {$nb->id} for notice '.$this->get_notice_id());
			parent::the_content();
            return;
        }

        $this->out->elementStart('span', array('class' => 'entry-content'));

        $attrs = array('href' => $nb->url,
                       'class' => 'bookmark-title');

        // Whether to nofollow
        $nf = common_config('nofollow', 'external');
        if ($nf == 'never' || ($nf == 'sometimes' and $this->out instanceof ShowstreamAction)) {
            $attrs['rel'] = 'external';
        } else {
            $attrs['rel'] = 'nofollow external';
        }

        $this->out->elementStart('h3');
        $this->out->element('a',
                      $attrs,
                      $nb->title);
        $this->out->elementEnd('h3');

        // Mentions look like "for:" tags
        $mentions = $this->item->getMentions();
        $tags = $this->item->getTags();

        if (!empty($mentions) || !empty($tags)) {

            $this->out->elementStart('ul', array('class' => 'bookmark-tags'));

            foreach ($mentions as $mention) {
                $other = Profile::staticGet('id', $mention);
                if (!empty($other)) {
                    $this->out->elementStart('li');
                    $this->out->element('a', array('rel' => 'tag',
                                             'href' => $other->profileurl,
                                             'title' => $other->getBestName()),
                                  sprintf('for:%s', $other->nickname));
                    $this->out->elementEnd('li');
                    $this->out->text(' ');
                }
            }

            foreach ($tags as $tag) {
                $tag = trim($tag);
                if (!empty($tag)) {
                    $this->out->elementStart('li');
                    $this->out->element('a',
                                  array('rel' => 'tag',
                                        'href' => Notice_tag::url($tag)),
                                  $tag);
                    $this->out->elementEnd('li');
                    $this->out->text(' ');
                }
            }

            $this->out->elementEnd('ul');
        }

        if (!empty($nb->description)) {
            $this->out->element('p',
                          array('class' => 'bookmark description'),
                          $nb->description);
        }

        $this->out->elementEnd('span');
    }
}

?>
