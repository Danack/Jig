

Compile block
-------------


Render block
------------

You can declare functions to process blocks of output. This is done with the bindProcessedBlock function. 


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
    
You can now use a 'warning' block in your templates:
    
.. code-block:: php

    {warning}
    Deprecated: The mysql extension is deprecated and will be removed in the future: use mysqli or PDO instead. 
    {/warning}


Which be rendered as:

.. code-block:: php

    <div class='warning'>
    <span class='warningTitle'>* Warning *</span>
        Deprecated: The mysql extension is deprecated and will be removed in the future: use mysqli or PDO instead.
    </div>


The end callable is required, the start block is optional.