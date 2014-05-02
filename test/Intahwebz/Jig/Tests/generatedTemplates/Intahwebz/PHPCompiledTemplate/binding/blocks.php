<?php

namespace Intahwebz\PHPCompiledTemplate\binding;

use Intahwebz\Jig\JigBase;

class blocks extends JigBase {

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

<?php  ob_start();  ?>
&euro;&yen;&trade;&lt;&gt;
<?php $this->jigRender->endProcessedBlock('htmlEntityDecode'); ?>


Hmm that was odd



<?php  ob_start();  ?>

Variable is: <?php echo \safeTextObject($this->getVariable("variable1"), ENT_QUOTES) ; ?>

<?php $this->jigRender->endProcessedBlock('htmlEntityDecode'); ?>
<?php 
    }

    }

        ?>