<?php

namespace Intahwebz\PHPCompiledTemplate;

use Intahwebz\Jig\JigBase;

class test123 extends JigBase {

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
?>Hello there <?php echo \safeTextObject($this->getVariable("title"), ENT_QUOTES) ; ?> <?php echo \safeTextObject($this->getVariable("user"), ENT_QUOTES) ; ?> !!!!
<?php 
    }

    }

        ?>