<?php

return array(


    array(
        'name' => 'blogPost',
        'pattern' => '/blogPost/{blogPostID}',
        'template' => 'blogPost',
        'mapping' => array(
            'Controller\\BlogPostExample', 'display'
        ),
        'defaults' => array(
            'blogPostID' => '0',
        ),
        'requirements' => array(
            'blogPostID' => '\d+',
        ),
    ),

    array(
        'name' => 'dataExample',
        'pattern' => '/dataExample',
        'template' => 'dataExample',
        'mapping' => array(
            'Controller\\DataExample', 'display'
        ),
    ),


    array(
        'name' => 'functionExample',
        'pattern' => '/functionExample',
        'template' => 'functionExample',
        'mapping' => array(
            'Controller\\FunctionExample', 'display'
        ),
    ),

    array(
        'name' => 'blockExample',
        'pattern' => '/blockExample',
        'template' => 'blockExample'
        //No controller needed.
    ),

    array(
        'name' => 'syntaxExample',
        'pattern' => '/syntaxExample',
        'template' => 'syntaxExample',
        'mapping' => array(
            'Controller\\SyntaxExample', 'display'
        ),
    ),

    array(
        'name' => 'index',
        'pattern' => '/',
        'template' => 'index'
    ),
);
