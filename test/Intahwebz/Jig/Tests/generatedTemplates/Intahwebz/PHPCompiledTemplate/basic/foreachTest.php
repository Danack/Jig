<?php

namespace Intahwebz\PHPCompiledTemplate\basic;

use Intahwebz\Jig\JigBase;

class foreachTest extends JigBase {

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




    function mainContent() {
?>



Direct: <?php foreach ( $this->getVariable("colors") as $color){  ?><?php  ob_start();  ?>
<?php echo \safeTextObject($color, ENT_QUOTES) ; ?>
<?php $this->jigRender->endProcessedBlock('trim'); ?><?php  }  ?>


Assigned: <?php foreach ( $this->getVariable("colors") as $color){  ?><?php  ob_start();  ?>
<?php echo \safeTextObject($color, ENT_QUOTES) ; ?>
<?php $this->jigRender->endProcessedBlock('trim'); ?><?php  }  ?>


Fromfunction: <?php foreach ( $this->call("getColors") as $color){  ?><?php  ob_start();  ?>
<?php echo \safeTextObject($color, ENT_QUOTES) ; ?>
<?php $this->jigRender->endProcessedBlock('trim'); ?><?php  }  ?>


<?php 
    }



    function renderInternal() {
?>
 <?php $this->mainContent();  ?> 
<?php 
    }

    }

        ?>