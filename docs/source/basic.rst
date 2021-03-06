Basic usage
===========

The simplest example of how to use Jig is:

.. code-block:: php

    use Jig\JigConfig;
    use Jig\JigRender;
    
    $jigConfig = new JigConfig(
        __DIR__."/templates/",
        __DIR__."/generatedTemplates/",
        "php.tpl",
        JigRender::COMPILE_ALWAYS
    );

    $provider = new \Auryn\Provider();
    $renderer = new JigRender($jigConfig, $provider);
    $contents = $renderer->renderTemplateFile('myFirstTemplate');

This will find the template file `./templates/myFirstTemplate.php.tpl`, compile it to `/generatedTemplates/Jig/PHPCompiledTemplate/myFirstTemplate.php`, run it and returns the content generated by the template to `$contents`.

Basic syntax
------------


All the constructs in Jig template are written with brackets with no space after the start bracket, or before the end bracket e.g.


.. code-block:: php
    
    Hello {$user}


There should not be any spaces between the brackets and the contents of them, if you want them to be processed by Jig.

.. code-block:: php

    Hello { $user }


See :ref:`syntaxReference` for the full list of constructs usable in Jig templates or the `example site <https://phpjig.com/>`_.


Injecting objects
-----------------

The main aim of Jig is to allow Dependency Injection in templates. 

This is easily achieved by using inject method. For example, you want to have an interface of `Promotions\BannerAd` and you want to have the appropriate banner ad injected into the page. In your applications config layer alias `Promotions\BannerAd` to the appropriate advert class: 

.. code-block:: php

    $injector->alias('Promotions\BannerAd', 'Promotions\SummerSale');

And then use the inject construct in the template:

.. code-block:: php

    {inject name='bannerAd' value='Promotions\BannerAd'}
    {$bannerAd->render() | nofilter}

An instance of a `Promotions\SummerSale` object will be injected into the template as the `$bannerAd` variable.

The `name` is the variable that the injected object will known by, and the value is the interface or classname that you want to have injected.


Filtering
---------

By default the output of all functions and variables will be escaped with `htmlentities($string, ENT_DISALLOWED | ENT_HTML401 | ENT_NOQUOTES, 'UTF-8')`. You can modify the filtering by appending a `| filter` to the function call or variable output
 
 
.. code-block:: php

     {someFunction() | nofilter}
     {$username | nofilter}

The allowed filters are:

============== ==================
Filter         Effect 
============== ==================
nofilter       Don't filter the output.
nooutput       Don't produce output. Used internally.
nophp          Don't run the PHP generated, instead just emit the PHP as text. In general this should only be used internally by the Jig renderer, but itmay be useful for debugging.
============== ==================



Embedding raw PHP
-----------------

Jig supports escaping to raw PHP with the standard blocks `<?php` to enter PHP mode, and `?>` to exit PHP mode. 


.. code-block:: php

    This is a template.
    <?php
        echo "This is some raw PHP";
        foo();
    ?>
    This is back to being a simple template.


The code will be running inside a method of the compile template class, which is extended from JigBase.


