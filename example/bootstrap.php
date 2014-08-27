<?php


$autoloader = require_once realpath(__DIR__).'/../vendor/autoload.php';

$autoloader->add('JigDemo', [realpath(__DIR__).'/']);
$autoloader->add('Jig', [realpath(__DIR__).'/compile/']);


use Jig\JigConfig;
use Jig\JigRender;
use JigDemo\Response\StandardHTTPResponse;

function bootstrapInjector() {

    $jigConfig = new JigConfig(
        __DIR__."/templates/",
        __DIR__."/compile/",
        'php.tpl',
        //JigRender::COMPILE_CHECK_MTIME
        JigRender::COMPILE_ALWAYS
        //JigRender::COMPILE_CHECK_EXISTS
    );

    $provider = new Auryn\Provider();
    $provider->share($jigConfig);
    $provider->alias('Jig\ViewModel', 'Jig\ViewModel\BasicViewModel');
    $provider->share($provider);

    return $provider;
}


/**
 * @param \Auryn\Provider $injector
 * @param $handler
 * @param $vars
 * @return \JigDemo\Response\Response $response;
 */
function process(\Auryn\Provider $injector, $handler, $vars) {

    $lowried = [];
    foreach ($vars as $key => $value) {
        $lowried[':'.$key] = $value;
    }

    $response = $injector->execute($handler, $lowried);

    return $response;
}


function servePage(\Auryn\Provider $injector, $routesFunction) {

    $dispatcher = FastRoute\simpleDispatcher($routesFunction);

    $httpMethod = 'GET'; //yay hard coding.
    $uri = '/';

    if (array_key_exists('REQUEST_URI', $_SERVER)) {
        $uri = $_SERVER['REQUEST_URI'];
    }
    
    $path = $uri;
    $queryPosition = strpos($path, '?');
    if ($queryPosition !== false) {
        $path = substr($path, 0, $queryPosition);
    }

    $routeInfo = $dispatcher->dispatch($httpMethod, $path);

    switch ($routeInfo[0]) {
        case FastRoute\Dispatcher::NOT_FOUND: {
            return new StandardHTTPResponse(404, $uri, "Not found");
        }

        case FastRoute\Dispatcher::METHOD_NOT_ALLOWED: {
            $allowedMethods = $routeInfo[1];
            // ... 405 Method Not Allowed
            return new StandardHTTPResponse(405, $uri, "Not allowed");
        }

        case FastRoute\Dispatcher::FOUND: {
            $handler = $routeInfo[1];
            $vars = $routeInfo[2];
            //TODO - support head?
            return process($injector, $handler, $vars);
        }
            
        default: {
            //Not supported
            //return new StandardHTTPResponse(404, $uri, "Not found");
            return null;
            break;
        }
    }
}


$routesFunction = function(FastRoute\RouteCollector $r) {
    //Category indices
    $r->addRoute(
        'GET',
        "/",
        ['JigDemo\Controller\Index', 'display']
    );

    $r->addRoute(
        'GET',
        "/bindingDataExample",
        ['JigDemo\Controller\BindingDataExample', 'display']
    );
    
    $r->addRoute(
        'GET',
        "/extend",
        ['JigDemo\Controller\Extend', 'display']
    );
    
    $r->addRoute(
        'GET',
        "/reuseExample",
        ['JigDemo\Controller\ReuseExample', 'display']
    );
    
    $r->addRoute(
        'GET',
        "/functionExample",
        ['JigDemo\Controller\FunctionExample', 'display']
    );
    
    $r->addRoute(
        'GET',
        "/blockExample",
        ['JigDemo\Controller\BlockExample', 'display']
    );
    
    $r->addRoute(
        'GET',
        "/syntaxExample",
        ['JigDemo\Controller\SyntaxExample', 'display']
    );
};

