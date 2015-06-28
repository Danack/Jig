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


class ExampleViewModel extends \Jig\ViewModel\BasicTemplateHelper {
    function testMethod() {
        return "This is a method in the ViewModel. It can be called without being explicitly bound.";
    }
}


interface ColorScheme {
    function getColors();
}

class PrimaryColorscheme implements ColorScheme {
    
    function getColors() {
        return ['red', 'green', 'blue'];
    }
}


$provider = new \Auryn\Provider();

$provider->alias('ColorScheme', 'PrimaryColorscheme');

$renderer = new JigRender($jigConfig, $provider);




$viewModel = new ExampleViewModel();


function warningBlockStart() {
    $output = "<div class='warning'>";
    $output .= "<span class='warningTitle'>* Warning *</span>";
    echo $output;
}

function warningBlockEnd($content) {
    $output = $content;
    $output .= "</div>";
    echo $output;
}

function boundFunction($username) {
    echo "Hello $username";
}

$viewModel->setVariable('user', "anonymouse user");
$viewModel->setVariable('text', "<i>this is <u>some</u> text</i>");


$viewModel->bindFunction('boundFunction', 'boundFunction');
$viewModel->bindFunction('boundCallable', function() {echo "This is a callable";});




$renderer->bindRenderBlock(
    'warning',          //Block name
    'warningBlockEnd',  //Block end callable
    'warningBlockStart' //Block start callable.
);



$contents = $renderer->renderTemplateFile('onePageExample', $viewModel);
echo $contents;

