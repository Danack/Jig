<?php

namespace Controller;

use Intahwebz\ViewModel;
use Mapper\BlogPostMapper;

class FormExample {

    private $viewModel;

    function __construct(ViewModel $viewModel) {
        $this->viewModel = $viewModel;
    }

    function display(\Form\CreateBlogPostForm $form, BlogPostMapper $blogPostMapper) {
        if ($form->initForm() == true) {
            $valid = $form->validate();
            if ($valid) {
                $values = $form->getRowValues('new');
                $blogPostMapper->saveBlogPost($values['title'], $values['text']);
                $form->reset();
                $this->viewModel->addStatusMessage("Blog post saved.");
                $form->addRowValues('new', []);
            }
        }
        else {
            $form->addRowValues('new', []);
        }

        $this->viewModel->setVariable('form', $form);
    }
}






 