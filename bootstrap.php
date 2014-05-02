<?php


$autoloader = require('./vendor/autoload.php');

$autoloader->add('Intahwebz', [realpath('./').'/test/']);
$autoloader->add(
    "Intahwebz\\PHPCompiledTemplate",
    [realpath('./').'/test/Intahwebz/Jig/Tests/generatedTemplates/']
);