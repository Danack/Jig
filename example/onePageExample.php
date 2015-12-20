<?php

use Auryn\Injector;
use Jig\JigConfig;

use Jig\Jig;

// Register some directories in the autoloader - we don't do this in composer.json
// to avoid any confusion with users of the library.
$autoloader = require_once realpath(__DIR__).'/../vendor/autoload.php';
$autoloader->add('JigDemo', [realpath(__DIR__).'/']);
$autoloader->add('Jig', [realpath(__DIR__).'/compile/']);


$injector = new Injector();

// Setting the Jig config
$jigConfig = new JigConfig(
    __DIR__."/templates/",
    __DIR__."/compile/",
    Jig::COMPILE_ALWAYS,
    "php.tpl"
);

// Tell the DIC that every class that needs an instance of JigConfig 
// should use this one.
$injector->share($jigConfig);

// Alias an interface to a concrete class so that it can be found in 
// the template.
$injector->alias('JigDemo\Model\ColorScheme', '\JigDemo\Model\PrimaryColorscheme');

// This is the template we will be compiling. 
$templateName = 'onePageExample';

// Create the Jig renderer
$jig = new Jig($jigConfig);

// Tell Jig to make sure the template is compiled.
$jig->compile($templateName);

// Get the classname that the template will be called
$className = $jig->getFQCNFromTemplateName($templateName);

// Call the template
$contents = $injector->execute([$className, 'render']);

echo $contents;
