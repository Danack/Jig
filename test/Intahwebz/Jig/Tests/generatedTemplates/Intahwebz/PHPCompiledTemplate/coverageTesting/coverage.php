<?php

namespace Intahwebz\PHPCompiledTemplate\coverageTesting;

use Intahwebz\Jig\JigBase;

class coverage extends JigBase {

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

<?php echo $this->getVariable("filteredVar") ; ?>


<?php echo \safeTextObject($test = 5, ENT_QUOTES) ; ?>


test is <?php echo \safeTextObject($test, ENT_QUOTES) ; ?>



    Hello
    
{* This is a comment inside a literal *}
    

    
    {$filteredVar}
    





<?php 
    }

    }

        ?>