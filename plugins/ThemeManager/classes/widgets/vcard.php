<?php

class VcardWidget extends ProfileWidget {
    static function run(array $args=array()) {
        $class = get_class();
        $widget = new $class($args);    // runs validate()
        $widget->show();
    }

    function show() {
        $this->the_vcard();
    }
}

?>
