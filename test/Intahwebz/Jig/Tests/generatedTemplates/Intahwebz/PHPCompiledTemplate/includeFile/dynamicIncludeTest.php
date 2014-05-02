<?php

namespace Intahwebz\PHPCompiledTemplate\includeFile;

use Intahwebz\Jig\JigBase;

class dynamicIncludeTest extends JigBase {

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
<?php $file = $this->getVariable('dynamicInclude');
 ?><?php echo $this->jigRender->includeFile($file) ?>




<?php 
    }

    }

        ?>