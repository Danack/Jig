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

* Customisable helpers.


## Features


inject

literal

block

extends

helper

include

filters


if

else

{$foo = 5}
{if $foo}
    foo is truthy
{else}
    foo is falsy
{/if}


bindRenderBlock

bindCompileBlock


## Invoking templates

```

function echoTemplateResponse(Jig\JigBase $template)
{
    $text = $template->render();
    echo $text;
}

function getTemplateSetupCallable($templateName) {
    $fn = function (JigRender $jigRender) use ($templateName) {
        $className = $jigRender->getClassName($templateName);
        $jigRender->checkTemplateCompiled($templateName);
        $alias = [];
        $alias['Jig\JigBase'] = $className;
        $di = new InjectionParams([], $alias, [], []);

        return new Tier('createTemplateResponse', $di);
    };

    return $fn;
}
```





## Running example

A set of examples can be found in the 'example' directory. They can be run by using the PHP built-in webserver with the command `php -S 0.0.0.0:8000 index.php` in the example directory, and then visiting http://127.0.0.1:8000/
