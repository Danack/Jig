<?php

namespace Mapper;

use Model\BlogPost;

use Intahwebz\Router;

class BlogPostList implements \IteratorAggregate  {

    private $blogPosts = array();

    //In a real mapper you would be getting this stuff from a database and so would
    //have a dependency on a database connection object.
    function __construct(Router $router, BlogPostMapper $blogPostMapper) {
        $this->blogPosts = $blogPostMapper->getAllBlogPosts();
    }

//    function getBlogPost($blogPostID) {
//        if ($blogPostID > 0 && $blogPostID < count($this->blogPosts)) {
//            return $this->blogPosts[$blogPostID];
//        }
//    }

    public function getIterator() {
        return new \ArrayIterator($this->blogPosts);
    }
}

 