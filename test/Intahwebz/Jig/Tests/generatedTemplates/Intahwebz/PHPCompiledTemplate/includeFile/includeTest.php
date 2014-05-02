<?php

namespace Intahwebz\PHPCompiledTemplate\includeFile;

use Intahwebz\Jig\JigBase;

class includeTest extends JigBase {

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
<?php echo $this->jigRender->includeFile('includeFile/includeEnd') ?>

This is an include test.

Include test passed.

<?php echo $this->jigRender->includeFile('includeFile/includeStart') ?>
<?php 
    }

    }

        ?>