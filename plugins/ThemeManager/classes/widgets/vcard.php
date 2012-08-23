<?php

class VcardWidget extends ProfileWidget {
    static function run($args=null) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    function show() {
        $this->the_vcard();
        $this->out->flush();
    }
}

?>
