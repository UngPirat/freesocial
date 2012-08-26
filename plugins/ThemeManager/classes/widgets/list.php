<?php

abstract class ListWidget extends ThemeWidget {
    protected $offset = 0;
    protected $num    = 5;

    protected $itemClass;
    protected $title;
    protected $widgetClass;

    function show() {
        $this->the_content();
        $this->out->flush();
    }

    protected function initialize() {
		parent::initialize();

        $this->loop = $this->get_loop();
    }

    // could be overloaded if you want stuff like prefilling notices etc.
    function get_loop() {
        return new ObjectLoop($this->get_list());
    }

    abstract function get_list();	// returns a DataObject with multiple entries
    abstract function the_item($item);

    function the_more() {
        return;
    }

    function the_empty() {
        $this->out->element('div', 'not-found', _('No items found'));
    }

    function the_loop() {
        $this->out->elementStart('ul');
        while ($this->loop->next()) :
            $this->out->elementStart('li', "list-item {$this->itemClass}");
            $this->the_item($this->loop->current());
            $this->out->elementEnd('li');
        endwhile;
        $this->out->elementEnd('ul');
    }

    function the_content() {
        $this->out->elementStart('div', "list widget {$this->widgetClass}");
        if (!empty($this->title)) {
            $this->out->element('h3', 'widget-title', $this->title);
        }
		if ($this->loop->count()) {
            $this->the_loop();
            $this->the_more();
        } else {
            $this->the_empty();
        }
        $this->out->elementEnd('div');
    }
}

?>
