<?php


$autoloader = require_once realpath(__DIR__).'/../vendor/autoload.php';

$autoloader->add('JigDemo', [realpath(__DIR__).'/']);
$autoloader->add('Jig', [realpath(__DIR__).'/compile/']);


use Auryn\Injector;
use Jig\JigConfig;
use Jig\Jig;
use JigDemo\Response\StandardHTTPResponse;
use JigDemo\Application\InjectionParams;
use JigDemo\Application\Tier;
use Jig\JigBase;

function bootstrapInjector() {

    $jigConfig = new JigConfig(
        __DIR__."/templates/",
        __DIR__."/compile/",
        Jig::COMPILE_CHECK_MTIME,
        'php.tpl'
    );

    $injector = new Injector();
    $injector->share($jigConfig);
    $injector->share('Jig\JigRender');
    $injector->share('Jig\Jig');
    $injector->share('Jig\JigConverter');
    $injector->share($injector); //yolo service locator

    return $injector;
}



function createTemplateResponse(JigBase $template)
{
    $text = $template->render();

    return new JigDemo\Response\TextResponse($text);
}

function getTemplateCallable($templateName, array $sharedObjects = [])
{
    $fn = function (Jig $jigRender) use ($templateName, $sharedObjects) {
        $className = $jigRender->getFQCNFromTemplateName($templateName);
        $jigRender->compile($templateName);

        $alias = [];
        $alias['Jig\JigBase'] = $className;
        $injectionParams = new InjectionParams($sharedObjects, $alias, [], []);

        return new Tier('createTemplateResponse', $injectionParams);
    };

    return new Tier($fn);
}


function getRouteCallable() {

    $dispatcher = FastRoute\simpleDispatcher('routesFunction');

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
            $params = InjectionParams::fromParams($vars);
            
            return new Tier($handler, $params);
        }
            
        default: {
            //Not supported
            return new StandardHTTPResponse(404, $uri, "Not found");
            break;
        }
    }
}


 function routesFunction(FastRoute\RouteCollector $r) {
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


function addInjectionParams(Injector $injector, Tier $tier)
{
    $injectionParams = $tier->getInjectionParams();
    
    if (!$injectionParams) {
        return;
    }
        
    foreach ($injectionParams->getAliases() as $original => $alias) {
        $injector->alias($original, $alias);
    }
    
    foreach ($injectionParams->getShares() as $share) {
        $injector->share($share);
    }
    
    foreach ($injectionParams->getParams() as $paramName => $value) {
        $injector->defineParam($paramName, $value);
    }
    
    foreach ($injectionParams->getDelegates() as $className => $callable) {
        $injector->delegate($className, $callable);
    }
}