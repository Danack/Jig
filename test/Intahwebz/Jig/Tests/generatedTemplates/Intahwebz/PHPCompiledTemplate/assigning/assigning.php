<?php

namespace Intahwebz\PHPCompiledTemplate\assigning;

use Intahwebz\Jig\JigBase;

class assigning extends JigBase {

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




    function renderInternal() {
?>

variable below

<?php echo \safeTextObject($this->getVariable("variable1"), ENT_QUOTES) ; ?>

<?php echo \safeTextObject($this->getVariable("variableArray")['index1'], ENT_QUOTES) ; ?>

<?php echo \safeTextObject($this->call("var_dump", $this->getVariable("variableObject")), ENT_QUOTES) ; ?>


<?php 
    }

    }

        ?>