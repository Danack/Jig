<?php

require_once "bootstrap.php";

use JigDemo\Response\Response;
use JigDemo\Tier;

if (php_sapi_name() == "cli-server") {
    if (strrpos($_SERVER['PHP_SELF'] ,'.css') === (strlen($_SERVER['PHP_SELF']) - strlen('.css')) ||
        strrpos($_SERVER['PHP_SELF'] ,'.js') === (strlen($_SERVER['PHP_SELF']) - strlen('.js'))) {
        //If the requests are for static files, tell the PHP server to 
        //serve them directly
        return false;
    }
}

$injector = bootstrapInjector();

$callable = 'getRouteCallable';

try {
    $count = 0;
    
    do {
        $result = $injector->execute($callable);
    
        if ($result instanceof Response) {
            $result->send();
            break;
        }
        else if ($result instanceof Tier) {
            addInjectionParams($injector, $result);
            $callable = $result->getCallable();
        }
        else {
            throw new \Exception("Return value of tier must be either a response or a tier");
        }
        $count++;
    } while ($count < 10);
}
catch(Jig\JigException $je) {
    echo "Error rendering template: ".$je->getMessage()."<br/>";
    echo nl2br($je->getTraceAsString());
}
catch(\Exception $e) {
    echo "Unexpected exception: " .$e->getMessage()."<br/>";
    echo nl2br($e->getTraceAsString());
}

