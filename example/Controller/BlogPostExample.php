<?php

namespace Controller;

use Intahwebz\ViewModel;
use Mapper\BlogPostList;

class BlogPostExample {

    private $viewModel;

    private $blogPostList;

    private $provider;

    function __construct(ViewModel $viewModel, BlogPostList $blogPostList, \Auryn\Provider $provider) {
        $this->viewModel = $viewModel;
        $this->blogPostList = $blogPostList;
        $this->provider = $provider;
    }

    function display($blogPostID) {
        $blogPost = $this->blogPostList->getBlogPost($blogPostID);

        if ($blogPost == null) {
            $this->viewModel->setTemplate('missingContent');
            return;
        }

        $this->viewModel->setVariable('blogPost', $blogPost);
    }
}

 