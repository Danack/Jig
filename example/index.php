<?php

require_once realpath(__DIR__).'/../vendor/autoload.php';

//function loader($class)
//{
//    $file = $class . '.php';
//    if (file_exists($file)) {
//        require $file;
//    }
//}
//
//spl_autoload_register('loader');


$jigConfig = new Intahwebz\Jig\JigConfig(
    __DIR__."/templates/",
    __DIR__."/compile/",
    'php.tpl',
    \Intahwebz\Jig\JigRender::COMPILE_CHECK_MTIME
);

$provider = new Auryn\Provider();

$provider->share($jigConfig);

$provider->alias(Intahwebz\Router::class, Intahwebz\Routing\Router::class);
$provider->share(Intahwebz\Router::class);

$provider->alias(Intahwebz\Request::class, Intahwebz\Routing\HTTPRequest::class);
$provider->share(Intahwebz\Request::class);
$provider->alias(Intahwebz\Response::class, Intahwebz\Routing\HTTPResponse::class);
$provider->share(Intahwebz\Response::class);

$provider->alias(Intahwebz\Domain::class, Intahwebz\DomainExample::class);
$provider->share(Intahwebz\Domain::class);
$provider->define(Intahwebz\DomainExample::class, [':domainName' => 'basereality.test']);


$provider->alias(Intahwebz\ObjectCache::class, Intahwebz\Cache\NullObjectCache::class);


$provider->define(
    Intahwebz\Routing\HTTPRequest::class,
    array(
        ':server' => $_SERVER,
        ':get' => $_GET,
        ':post' => $_POST,
        ':files' => $_FILES,
        ':cookie' => $_COOKIE
    )
);

$routerParams = array(
    ':routeCollectionName' => 'jigrouting.test',
    ':pathToRouteInfo' => realpath(__DIR__)."/routing/jigrouting.php"
);
$provider->define(\Intahwebz\Routing\Router::class, $routerParams);

$viewModel = $provider->make(Intahwebz\ViewModel\BasicViewModel::class);
$jigRenderer = $provider->make(Intahwebz\Jig\JigRender::class);

$jigRenderer->bindViewModel($viewModel);




/** @var  $router \Intahwebz\Request */
$request = $provider->make(Intahwebz\Request::class);

/** @var  $router \Intahwebz\Router */
$router = $provider->make(Intahwebz\Router::class);

/** @var  $response \Intahwebz\Response */
$response = $provider->make(Intahwebz\Response::class);


$route = $router->getRouteForRequest($request);

$mapping = $route->getMapping();

if ($mapping != null) {
    while ($route != false) {
        $mergedParams = $route->getMergedParameters($request);
        $lowried = array();
        foreach ($mergedParams as $key => $value) {
            $lowried[':'.$key] = $value;
        }

        $classPath = $mapping->getClassPath();
        $controller = $provider->make($classPath);

        /** @var $controller \Intahwebz\Controller\Controller  */
        $controller->init($viewModel, $response);

        $route = $provider->execute(array($controller, $mapping->getMethodName()), $lowried);
    }
}


$lastTemplate = $route->getTemplate();
$jigRenderer->renderTemplateFile($lastTemplate);