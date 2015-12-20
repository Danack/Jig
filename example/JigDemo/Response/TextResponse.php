<?php


namespace JigDemo\Response;



class TextResponse implements \JigDemo\Response\Response
{
    private $text;

    function __construct($text) {
        $this->text = $text;
    }

    function send() {
        echo $this->text;
    }
}

 