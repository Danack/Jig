Reusing templates
=================


Including other templates
-------------------------

You can include other templates by using the `include` directive:

.. code-block:: php

    {include file='includeStart'}
    This is an include test.
    Include test passed.
    {include file='includeEnd'}
    ..

This will include (and render) the `includeStart` at the start of the template, and `includeEnd` template at the end of the template.


The file to include can be set via a variable:

.. code-block:: php

    {include file=$dynamicInclude}



Extending templates
-------------------

Extending templates allows you to define blocks of output that get replaced by the template that extends the base template. For example, in the template `customPage`:

.. code-block:: php

    {extends file='standardHTMLPage'}

    {block name='mainContent'}
        This is a customPage.
    {/block}

   
In the template standardHTMLPage:
  
.. code-block:: php

    {include file='pageStart'}
    
    {block name='mainContent'}
        This block gets replaced by the extended version.
    {/block}
    
    {include file='pageEnd'}


The block `mainContent` in the standardHTMLPage template will be replaced by the contents of the `mainContent` block from the customPage template. This allows you to define standard pages and then customize them as required.



Dynamic extend
--------------

Be warned! This is probably a bad idea. It is however very useful.


Imagine you have an image gallery on your website. You want to be able to display a page from the image gallery as a full webpage, but also when the user clicks 'next' the next page of content should be fetched via Ajax and just replace the current image gallery div.

You can achieve this by using the dynamicExtends construct in the imageGallery template.

.. code-block:: php
    :filename: imageGallery.php.tpl

    {dynamicExtends file='parent'}

    {block name='content'}
        {$imageGallery->render() | nofilter}
    {/block}


We then setup an Ajax parent template:

.. code-block:: php
    :filename: ajaxContent.php.tpl

    <div class='ajaxContent'>
    {block name='content'}
        This gets replaced by the child block.
    {/block}
    </div>
    
And a template to render:
    
.. code-block:: php
    :filename: fullHTMLPage.php.tpl

    {include file='htmlPageStart'}
    <div class='ajaxContent'>
    {block name='content'}
        
    {/block}
    </div>
    {include file='htmlPageEnd'}


.. code-block:: php

    $this->jigRenderer->mapClasses(array('parent' => 'fullHTMLPage'));





