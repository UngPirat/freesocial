<?php

class ConversationLoop extends ObjectLoop {
    function initialize() {
        parent::initialize();

        $convId = array();
        foreach ($this->list as $notice) {
            if (!is_a($notice, 'Notice')) {
                throw new ServerException('Attempting to fill conversations on a non-Notice object');
            }
            $convId[] = $notice->conversation;
        }
        $convId = array_unique($convId);
        $this->list = Memcached_DataObject::listGet('Notice', 'conversation', $convId);
    }

    function get_id() {
        return key($this->list);
    }

    function get_paging($page) {
        $page  = (0+$page === 0 ? 1 : 0+$page);    // convert to (int)
        if ($page < 1) {
            throw new ClientException('Invalid paging arguments');
        }

        $pages = array();
        if ($page > 1) {
            $pages['next']   = $page - 1;
        }
        if ($this->count() > $this->num) {
            $pages['prev']   = $page + 1;
        }
        if (isset($pages['next']) || isset($pages['prev'])) {
            $pages['current'] = $page;
        }
        return $pages;
    }
}
