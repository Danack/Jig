<?php


$autoloader = require('./vendor/autoload.php');

$autoloader->add('Jig', [realpath('./').'/test/']);
$autoloader->add(
    "Jig\\PHPCompiledTemplate",
    [realpath(realpath('./').'/tmp/generatedTemplates/')]
);

