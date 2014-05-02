<?php

namespace Intahwebz\PHPCompiledTemplate\block;

use Intahwebz\Jig\JigBase;

class spoiler extends JigBase {

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

<?php $this->jigRender->startProcessedBlock('spoiler'); ?><?php  ob_start();  ?>
This is in a spoiler?

<?php $this->jigRender->endProcessedBlock('spoiler'); ?>
    

<?php 
    }



    function renderInternal() {
?>
 <?php $this->mainContent();  ?> 
<?php 
    }

    }

        ?>