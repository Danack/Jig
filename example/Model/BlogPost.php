<?php



namespace Model;


class BlogPost {

    public $id;
    public $url;
    public $title;
    public $text;

    function __construct($id, $url, $title, $text) {
        $this->id = $id;
        $this->url = $url;
        $this->text = $text;
        $this->title = $title;
    }
}
 