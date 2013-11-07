<?php

require_once realpath(__DIR__).'/../vendor/autoload.php';

use Intahwebz\Jig\JigConfig;

$jigConfig = new JigConfig(
    __DIR__."/templates/",
    __DIR__."/compile/",
    'php.tpl',
    \Intahwebz\Jig\JigRender::COMPILE_CHECK_MTIME
);

$provider = new Auryn\Provider();

$provider->share($jigConfig);

$viewModel = $provider->make('Intahwebz\ViewModel\BasicViewModel');
$jigRenderer = $provider->make('Intahwebz\Jig\JigRender');

$jigRenderer->bindViewModel($viewModel);
$jigRenderer->renderTemplateFile('index');
