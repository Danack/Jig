<?php

namespace Intahwebz\PHPCompiledTemplate\dynamicExtend;

use Intahwebz\Jig\JigBase;

class parent1 extends JigBase {

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




    function title() {
?>
    
    This is a title.
    

<?php 
    }



    function content() {
?>

    This is the parent 1 content.


<?php 
    }



    function renderInternal() {
?>
 <?php $this->title();  ?> 

This is the parent 1 start.


 <?php $this->content();  ?> 


This is the parent 1 end.

<?php 
    }

    }

        ?>