<?php


$autoloader = require_once realpath(__DIR__).'/../vendor/autoload.php';

$autoloader->add('Jig', [realpath(__DIR__).'/compile/']);

use Jig\JigConfig;
use Jig\JigRender;

$jigConfig = new JigConfig(
    __DIR__."/templates/",
    __DIR__."/compile/",
    "php.tpl",
    JigRender::COMPILE_ALWAYS
);

$provider = new \Auryn\Provider();
$renderer = new JigRender($jigConfig, $provider);
$contents = $renderer->renderTemplateFile('standalone');

echo $contents;


$contents = $renderer->renderTemplateFromString("Hello there", "Example1");


echo "The contents are:".PHP_EOL;
echo $contents;