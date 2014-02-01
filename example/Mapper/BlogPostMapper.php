<?php


namespace Mapper;

use Intahwebz\Router;

class BlogPostMapper {


    private $router;

    function __construct(Router $router) {
        $this->router = $router;
    }

    function getAllBlogPosts() {
        $filenames = glob ('data/blogpost_*.json');

        $blogPosts = array();

        foreach ($filenames as $filename) {
            $number = preg_match("/(\d+)/", $filename, $matches)
                ? (int)$matches[1]
                : null;

            if ($number !== null) {
                $blogPosts[] = $this->getBlogPost($number);
            }
        }

        return $blogPosts;
    }

    function getFilename($blogPostID) {
        return 'data/blogpost_'.$blogPostID.'.json';
    }


    function getBlogPost($blogPostID) {

        $filename = $this->getFilename($blogPostID);
        $fileContents = @file_get_contents($filename);

        if ($fileContents == false) {
            return null;
        }

        $blogPostData = json_decode($fileContents, true);
        return new \Model\BlogPost(
            $blogPostID,
            $this->router->generateURLForRoute('blogPost', ['blogPostID' => $blogPostID]),
            $blogPostData['title'],
            $blogPostData['text']
        );
    }

    function saveBlogPost($title, $text) {
        $blogPost = array();

        $blogPost['title'] = $title;
        $blogPost['text'] = $text;

        $filename = $this->getNextFilename();

        $written = file_put_contents($filename, json_encode($blogPost));

        if ($written == false) {
            throw new \RuntimeException("Failed to write to file $filename.");
        }
    }


    function getNextFilename() {
        for($x = 0 ; $x < 1000 ; $x++) {

            $filename = $this->getFilename($x);
            if (@file_exists($filename) == false) {
                return $filename;
            }
        }

        throw new \RuntimeException("Failed to create filename.");
    }

}

 