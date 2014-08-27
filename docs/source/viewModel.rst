.. _viewModel:


Using a ViewModel
=================


Although it is best to insert all dependencies in to the template as objects, it is sometimes *waaaay* more convenient to be able insert just some variables or methods to be used in the template.


Binding variables
-----------------

Variables can be explicitly bound to a VideModel with the setVariable method. This allows you to use the variable in a template. e.g. binding two variables to `title` and `user` in the PHP code setting up the ViewModel:


.. code-block:: php
    :filename: phpRender.php

    $viewModel = new BasicViewModel();
    $viewModel->setVariable('title', 'Mr');
    $viewModel->setVariable('user', 'Danack');
    $jigRenderer->bindViewModel($viewModel);

Allows the variables to be used in a template:

.. code-block:: php
    :filename: testTemplate.php.tpl

    Hello {$title} {$user}


Binding functions
-----------------

.. code-block:: php

    function printTime($user) {
        $hourOfDay = date('G', time());
        if (intval($hourOfDay) < 12) {
            echo "Good morning $user";
        }
        else {
            echo "Hello $user";
        }
    }

    $viewModel = new BasicViewModel();
    $viewModel->bindFunction('showTime', 'printTime');
    $jigRenderer->bindViewModel($viewModel);
    
.. code-block:: php

    {showTime()}


Methods on the view model
-------------------------

Any method of the ViewModel bound to the JigRender can be used in the template without being explicitly bound.

.. code-block:: php
    :filename: setup.php
    
    class WebsiteViewModel extends BasicViewModel {
        function showLogo() {
            echo "<span class='logo'><img src='/websitelogo.png'></span>";
        }
    }
    
    $viewModel = new BasicViewModel();
    $jigRenderer->bindViewModel($viewModel);
    
    
.. code-block:: php
    :filename: testTemplate.php.tpl
   
    {showLogo()}
    
    
Functions bound explicitly through `$viewModel->bindFunction(...);` have priority when their names clash with those of the methods of the ViewModel. 