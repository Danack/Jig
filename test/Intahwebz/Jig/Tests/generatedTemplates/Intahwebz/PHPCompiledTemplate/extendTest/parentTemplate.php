<?php

namespace Intahwebz\PHPCompiledTemplate\extendTest;

use Intahwebz\Jig\JigBase;

class parentTemplate extends JigBase {

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




    function firstBlock() {
?>
    This is the first parent block.

<?php 
    }



    function secondBlock() {
?>
    This is the second parent block.

<?php 
    }



    function renderInternal() {
?>

This is before the blocks.

 <?php $this->firstBlock();  ?> 


This is between the blocks

 <?php $this->secondBlock();  ?> 


This is after the blocks.



<?php 
    }

    }

        ?>