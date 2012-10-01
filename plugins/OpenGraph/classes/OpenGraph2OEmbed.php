<?php

class OpenGraph2OEmbed extends OpenGraph {
    public $element;
    public $type;
    public $id;
    public $title;
    public $summary;
    public $content;
    public $owner;
    public $link;
    public $source;
    public $avatarLinks = array();
    public $geopoint;
    public $poco;
    public $displayName;

    public $thumbnail;
    public $largerImage;
    public $description;
    public $extra = array();

    public function onProcessLink($file, &$content, &$metadata) {
        
    }
}

?>
