<?php

namespace Controller;

use Intahwebz\ViewModel;
use Mapper\BlogPostMapper;

class BlogPostExample {

    private $viewModel;

    private $blogPostMapper;

    private $provider;

    function __construct(ViewModel $viewModel, BlogPostMapper $blogPostMapper, \Auryn\Provider $provider) {
        $this->viewModel = $viewModel;
        $this->blogPostMapper = $blogPostMapper;
        $this->provider = $provider;
    }

    function display($blogPostID) {
        $blogPost = $this->blogPostMapper->getBlogPost($blogPostID);

        if ($blogPost == null) {
            $this->viewModel->setTemplate('missingContent');
            return;
        }

        $this->viewModel->setVariable('blogPost', $blogPost);
    }
}

 