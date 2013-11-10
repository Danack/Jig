<?php

require_once realpath(__DIR__).'/../vendor/autoload.php';

spl_autoload_register('loader');

if (strrpos($_SERVER['PHP_SELF'] ,'.css') === (strlen($_SERVER['PHP_SELF']) - strlen('.css')) ||
    strrpos($_SERVER['PHP_SELF'] ,'.js') === (strlen($_SERVER['PHP_SELF']) - strlen('.js'))) {
    return false;
}



$provider = setupProvider();

try{
    processRequest($provider);
}
catch(\Exception $e) {
    echo "Exception caught: ".$e->getMessage()."\n";
    echo $e->getTraceAsString();
}


function setupProvider() {

    $jigConfig = new Intahwebz\Jig\JigConfig(
        __DIR__."/templates/",
        __DIR__."/compile/",
        'php.tpl',
        \Intahwebz\Jig\JigRender::COMPILE_CHECK_MTIME
    );

    $provider = new Auryn\Provider();

    $provider->share($jigConfig);

    $standardSharedObjects = [
        Intahwebz\Router::class => Intahwebz\Routing\Router::class,
        Intahwebz\Request::class => Intahwebz\Routing\HTTPRequest::class,
        Intahwebz\Response::class => Intahwebz\Routing\HTTPResponse::class,
        Intahwebz\ViewModel::class => Intahwebz\ViewModel\BasicViewModel::class,
        Intahwebz\Domain::class => Intahwebz\DomainExample::class,
        Intahwebz\Session::class => Intahwebz\Session\Session::class,
    ];

    foreach ($standardSharedObjects as $interfaceName => $implementationName) {
        $provider->alias($interfaceName, $implementationName);
        $provider->share($interfaceName);
    }

    $standardLogger = new Intahwebz\Logger\NullLogger();

    $provider->alias(Psr\Log\LoggerInterface::class, get_class($standardLogger));
    $provider->share($standardLogger);

    $provider->alias(Intahwebz\ObjectCache::class, Intahwebz\Cache\NullObjectCache::class);

    $provider->define(Intahwebz\DomainExample::class, [':domainName' => 'basereality.test']);
    $provider->share(Intahwebz\DomainExample::class);

    $provider->define(Intahwebz\Session\Session::class, [':sessionName' => 'jigtest']);
    $provider->share(Intahwebz\Session\Session::class);

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
        ':pathToRouteInfo' => realpath(__DIR__)."/data/jigrouting.php"
    );

    $provider->define(\Intahwebz\Routing\Router::class, $routerParams);
    $provider->share($provider);

    return $provider;
}


function processRequest(\Auryn\Provider $provider) {

    $viewModel = $provider->make(Intahwebz\ViewModel\BasicViewModel::class);

    /** @var  $router \Intahwebz\Request */
    $request = $provider->make(Intahwebz\Request::class);

    /** @var  $router \Intahwebz\Router */
    $router = $provider->make(Intahwebz\Router::class);

    $provider->share($router);

    /** @var  $response \Intahwebz\Response */
    $response = $provider->make(Intahwebz\Response::class);

    /** @var $jigRenderer Intahwebz\Jig\JigRender */
    $jigRenderer = $provider->make(Intahwebz\Jig\JigRender::class);

    $jigRenderer->bindViewModel($viewModel);

    $route = $router->getRouteForRequest($request);

    $mapping = $route->getMapping();

    $viewModel->setTemplate($route->getTemplate());

    if ($mapping != null) {

        while ($route != null) {

            $routeTemplate = $route->getTemplate();
            if ($routeTemplate != null) {
                $viewModel->setTemplate($routeTemplate);
            }

            $mergedParams = $route->getMergedParameters($request);
            $lowried = array();
            foreach ($mergedParams as $key => $value) {
                $lowried[':'.$key] = $value;
            }

            $viewModel->setMergedParams($lowried);

            $classPath = $mapping->getClassPath();
            $controller = $provider->make($classPath);
            $route = $provider->execute(array($controller, $mapping->getMethodName()), $lowried);
        }
    }

    $jigRenderer->renderTemplateFile($viewModel->getTemplate());
}


function loader($class) {

    $file = realpath(__DIR__).'/'.str_replace('\\', '/', $class).'.php';

    if (file_exists($file)) {
        require $file;

        if(class_exists($class, false) == false) {
            echo "failed to load class $class";
            exit(0);
        }

        return true;
    }
}

