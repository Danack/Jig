<?php

require_once realpath(__DIR__).'/../vendor/autoload.php';
spl_autoload_register('loader');




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
        'Intahwebz\Router'      => 'Intahwebz\Routing\Router',
        'Intahwebz\Request'     => 'Intahwebz\Routing\HTTPRequest',
        'Intahwebz\Response'    => 'Intahwebz\Routing\HTTPResponse',
        'Intahwebz\ViewModel'   => 'Intahwebz\ViewModel\BasicViewModel',
        'Intahwebz\Domain'      => 'Intahwebz\DomainExample',
        'Intahwebz\Session'     => 'Intahwebz\Session\Session',
    ];

    foreach ($standardSharedObjects as $interfaceName => $implementationName) {
        $provider->alias($interfaceName, $implementationName);
        $provider->share($interfaceName);
    }

    $standardLogger = new Intahwebz\Logger\NullLogger();

    $provider->alias('Psr\Log\LoggerInterface', get_class($standardLogger));
    $provider->share($standardLogger);

    $provider->alias('Intahwebz\ObjectCache', 'Intahwebz\Cache\NullObjectCache');
    $provider->define('Intahwebz\DomainExample', [':domainName' => 'basereality.test']);
    $provider->share('Intahwebz\DomainExample');
    $provider->define('Intahwebz\Session\Session', [':sessionName' => 'jigtest']);
    $provider->share('Intahwebz\Session\Session');

    $provider->define(
        'Intahwebz\Routing\HTTPRequest',
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

    $provider->define('Intahwebz\Routing\Router', $routerParams);
    $provider->share($provider);

    return $provider;
}


function processRequest(\Auryn\Provider $provider) {

    $viewModel = $provider->make('Intahwebz\ViewModel\BasicViewModel');

    /** @var  $router \Intahwebz\Request */
    $request = $provider->make('Intahwebz\Request');

    /** @var  $router \Intahwebz\Router */
    $router = $provider->make('Intahwebz\Router');

    $provider->share($router);

    /** @var  $response \Intahwebz\Response */
    $response = $provider->make('Intahwebz\Response');

    /** @var $jigRenderer Intahwebz\Jig\JigRender */
    $jigRenderer = $provider->make('Intahwebz\Jig\JigRender');

    $jigRenderer->bindViewModel($viewModel);

    $matchedRoute = $router->matchRouteForRequest($request);

    $route = $matchedRoute->getRoute();
    $mapping = $route->get('mapping');

    $viewModel->setTemplate($matchedRoute->getRoute()->get('template'));

    if ($mapping != null) {

        while ($route != null) {

            $routeTemplate = $route->get('template');
            if ($routeTemplate != null) {
                $viewModel->setTemplate($routeTemplate);
            }

            $mergedParams = $matchedRoute->getMergedParameters($request);
            $lowried = array();
            foreach ($mergedParams as $key => $value) {
                $lowried[':'.$key] = $value;
            }

            $viewModel->setMergedParams($lowried);

            //var_dump($mapping);

//            if (count($mapping) == 2) {
//                $classPath = $mapping[0];
//                $method = $mapping[1];
//                $controller = $provider->make($classPath);
                /** @var $route \Intahwebz\Route */
                //$route = $provider->execute(array($controller, $method), $lowried);
                $route = $provider->execute($mapping, $lowried);
            //}
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
