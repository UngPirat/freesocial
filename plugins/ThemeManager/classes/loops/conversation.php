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
}
