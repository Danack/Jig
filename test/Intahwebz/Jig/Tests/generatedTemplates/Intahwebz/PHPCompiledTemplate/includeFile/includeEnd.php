<?php

namespace Intahwebz\PHPCompiledTemplate\includeFile;

use Intahwebz\Jig\JigBase;

class includeEnd extends JigBase {

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
?></body>


</html>
<?php 
    }

    }

        ?>