<?php


$autoloader = require('./vendor/autoload.php');

$autoloader->add('Jig', [realpath('./').'/test/']);
$autoloader->add(
    "Jig\\PHPCompiledTemplate",
    [realpath(realpath('./').'/tmp/generatedTemplates/')]
);


//Used in a test
function testFunction1() {
    echo "This is a global function.";
}