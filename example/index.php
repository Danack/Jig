<?php


require_once "bootstrap.php";

if (php_sapi_name() == "cli-server") {
    if (strrpos($_SERVER['PHP_SELF'] ,'.css') === (strlen($_SERVER['PHP_SELF']) - strlen('.css')) ||
        strrpos($_SERVER['PHP_SELF'] ,'.js') === (strlen($_SERVER['PHP_SELF']) - strlen('.js'))) {
        
        //If the requests are for static files, tell the 
        return false;
    }
}

$injector = bootstrapInjector();

try {
    $response = servePage($injector, $routesFunction);

    if ($response != null) {
        $response->send();
    }
}
catch(Jig\JigException $je) {
    echo "Error rendering template: ".$je->getMessage();
}
catch(\Exception $e) {
    echo "Somethings fucky: " .$e->getMessage();
}
