# Jig - Lightweight, fast, testable PHP templating system.

Jig - "A device that holds a piece of machine work and guides the tools operating on it."

Or to put it another way, a jig allows you to work fast with sharp tools without cutting your fingers off.


<table>
    <thead>
        <tr>
            <th>Build status</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <a href="https://travis-ci.org/Danack/Jig ">
                    <img src="https://travis-ci.org/Danack/Jig.png" alt="Latest Stable Version" style="max-width:100%;">
                </a>
            </td>
        </tr>
    </tbody>
</table>


## What

Jig is a template renderer that promotes the view layer to be a first class citizen in your application.

By using it, all of your templates are unit-testable as it does not use the 'service locator' pattern like most other templating systems do.

* Compiles to PHP class for super-duper performance.

* Uses real Dependency injection in templates, to allow unit testing of views.  

* Super lightweight. Zero overhead when templates are already compiled when used with APC/OPCache

* Simple but powerful plugin system.

## Documentation

Please run this to view the documentation:

```
git clone https://github.com/danack/TierJigDocs
cd TierJigDocs/
composer install
mkdir -p var/cache
php -S localhost:8000 -t public
```



## The "I know what I'm doing" just show me some code" guide to using Jig

```
use Auryn\Injector;
use Jig\JigConfig;
use Jig\Jig;

// Create a JigConfig object
$jigConfig = new JigConfig(
    //The directory the source templates are in
    __DIR__."/../templates/",
    //The directory the generated PHP code will be written to.
    __DIR__."/../var/generatedTemplates/",
    // How to check if the templates need compiling.
    Jig::COMPILE_CHECK_MTIME,
    // The extension our templates will have.
    "php.tpl"
);

// Create a Jig renderer with our config
$jig = new Jig($jigConfig);

// Check the template is compiled to PHP and get the classname of the
// generated PHP template.
$className = $jig->compile("gettingStarted/basic");

// Create a DIC that can create an instance of the template
$injector = new Injector();

$injector->alias('Jig\Escaper', 'Jig\Bridge\ZendEscaperBridge');

// Create an instance of the template
$templateInstance = $injector->make($className);

// Render the template and send it to the user.
$output = $templateInstance->render();

// Send the output to the user
echo $output;

// Alternatively if your DIC supports direct execution 
//$output = $injector->execute([$className, 'render']);
```
