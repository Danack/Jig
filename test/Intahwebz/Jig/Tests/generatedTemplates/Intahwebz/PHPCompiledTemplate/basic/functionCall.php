<?php

namespace Intahwebz\PHPCompiledTemplate\basic;

use Intahwebz\Jig\JigBase;

class functionCall extends JigBase {

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
?><?php echo \safeTextObject($this->call("someFunction", '$("#myTable").tablesorter();'), ENT_QUOTES) ; ?>


<?php if ($this->call("checkRole", 'admin')){ ?>
    checkRole works
<?php } ?>
<?php 
    }

    }

        ?>