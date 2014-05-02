<?php

namespace Intahwebz\PHPCompiledTemplate\binding;

use Intahwebz\Jig\JigBase;

class binding extends JigBase {

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

Function test

<?php echo \safeTextObject($this->call("testFunction1"), ENT_QUOTES) ; ?>


<?php echo \safeTextObject($this->call("testFunction2"), ENT_QUOTES) ; ?>


<?php echo \safeTextObject($this->call("testFunction3"), ENT_QUOTES) ; ?>
<?php 
    }

    }

        ?>