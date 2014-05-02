<?php

namespace Intahwebz\PHPCompiledTemplate\dynamicExtend;

use Intahwebz\Jig\JigBase;

class parent2 extends JigBase {

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




    function content() {
?>

    This is the parent 1 content.


<?php 
    }



    function renderInternal() {
?>


This is the parent 2 start.


 <?php $this->content();  ?> 


This is the parent 2 end.

<?php 
    }

    }

        ?>