<?php

namespace Intahwebz\PHPCompiledTemplate\extendTest;

use Intahwebz\PHPCompiledTemplate\extendTest\parentTemplate;

class child extends parentTemplate {

    private $injections = array(
    );



    function getInjections() {
            $parentInjections = parent::getInjections();

            return array_merge($parentInjections, $this->injections);
        }

   function getVariable($name) {
            if (property_exists($this, $name) == true) {
                return $this->{$name};
            }

            return parent::getVariable($name);
        }




    function secondBlock() {
?>
    This is the second child block.

<?php 
    }

    }

        ?>