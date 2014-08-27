.. _syntaxReference:

Syntax reference
================


Use a variable
--------------


.. code-block:: php

    Hello {$username} !

Call a function
---------------

.. code-block:: php

    {greet($username)} !


Assign a variable
-----------------

.. code-block:: php

    {$username = getUserName()}
    {greet($username)} !



Comments
--------

Comments are compiled to PHP comments i.e. aren't shown on the webpage as HTML comments

.. code-block:: php

    {* This does nothing *}

See :ref:`viewModel` for how to setup variables and functions.




Literal
-------

.. code-block:: php

    {literal}
       {* This not a comment, it is shown with the brackets in the output *}
    {/literal}


if statement 
------------

.. code-block:: php

    {if $foo}
        foo is not falsy.
    {/if}
    
    {if $pi == 3}
        pi is 3.
    {/if}
    
    
else statement
--------------

.. code-block:: php

    {if $foo}
        foo is not falsy.
    {else}
        foo is falsy.
    {/if}
    
    

foreach
-------
 
.. code-block:: php

    {foreach $navLinks as $navLink}
        {$navLink->render()]}<br/>
    {/foreach}


isset
-----

.. code-block:: php

    {isset($showLogo) && $showLogo}
        <img src='/logo.png' />
    {/if}

trim
----

.. code-block:: php

    <div>{trim}
        {$debugText}
    {/trim}</div>

Outputs the same that `echo "<div>$debugText</div>";` i.e. all the white-space is trimmed.


function calling
----------------

.. code-block:: php

    {showLogo()}
    


inject object
-------------

.. code-block:: php

    $injector->alias('Promotions\BannerAd', 'Promotions\SummerSale');
    {inject name='bannerAd' value='Promotions\BannerAd'}
    {$bannerAd->render() | nofilter}
    
    
filter output
-------------

.. code-block:: php

     {someFunction() | nofilter}
     
The options for filtering are 'nofilter', 'nooutput', and 'nophp'.
     


Binding blocks
--------------

.. code-block:: php

    function warningBlockStart() {
        $output = "<div class='warning'>";
        $output .= "<span class='warningTitle'>* Warning *</span>";
        echo $output;
    }

    function warningBlockEnd($content) {
        $output = $content;
        $output .= "</div>";
        echo $output;
    }

    $jigRender->bindProcessedBlock(
        'warning',          //Block name
        'warningBlockEnd',  //Block end callable
        'warningBlockStart' //Block start callable.
    );

.. code-block:: php

    {warning}
    Deprecated: The mysql extension is deprecated and will be removed in the future: use mysqli or PDO instead. 
    {/warning}


Binding functions
-----------------

.. code-block:: php

    {greet()} {$username}

Binding variables
-----------------

.. code-block:: php

    Hello there {$username}

Including other templates
-------------------------

.. code-block:: php

    {include file='includeStart'}


Injecting dependencies
----------------------

.. code-block:: php

    {inject name='bannerAd' value='Promotions\BannerAd'}
    {$bannerAd->render() | nofilter}


Embedding PHP
-------------

.. code-block:: php

    This is a template.
    <?php
        echo "This is some raw PHP";
        foo();
    ?>


Extending other templates
-------------------------

.. code-block:: php

    {extends file='standardHTMLPage'}

    {block name='mainContent'}
        This is a customPage.
    {/block}

.. rubric:: Dynamic extend









   
   
   
   
   
   
   