<?php

namespace JigTest\PlaceHolder;

class SiteNavLinks implements \IteratorAggregate
{
    public $links = array();

    public function __construct()
    {
        $this->links = array(
            array( 'url' => 'http://flickr.com/danack/', 'description' => 'Flickr'),
            array( 'url' => 'http://youtube.com/BaseReality/', 'description' => 'Youtube'),
            array( 'url' => 'http://www.twitter.com/MrDanack', 'description' => 'Twitter'),
            array( 'url' => 'http://stackoverflow.com/users/778719/danack57', 'description' => 'Stackoverflow'),

            array( 'url' => 'http://blog.basereality.com', 'description' => 'Blog'),
            //array( 'url' => $router->generateURLForRoute('CV'), 'description' => 'C.V.'),
        );
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->links);
    }
}
