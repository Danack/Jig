<?php


namespace Mapper;


class BlogPost extends \Model\BlogPost {

    // In a real environment this would be accessing the database rather than
    // a hard-coded list of objects.
    function __construct(BlogPostList $blogPostList, $blogPostID) {
        $blogPost = $blogPostList->getBlogPost($blogPostID);

        if ($blogPost == null) {
            throw new \InvalidArgumentException("BlogPost $blogPostID could not be found.");
        }

        $this->id = $blogPost->id;
        $this->url = $blogPost->url;
        $this->title = $blogPost->title;
        $this->text = $blogPost->text;
    }


}

 