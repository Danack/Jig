<?php

namespace Intahwebz\PHPCompiledTemplate\basic;

use Intahwebz\Jig\JigBase;

class basic extends JigBase {

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


This is actually a template.

    
Hello there <?php echo \safeTextObject($this->getVariable("title"), ENT_QUOTES) ; ?> <?php echo \safeTextObject($this->getVariable("user"), ENT_QUOTES) ; ?> !!!!

<?php

for($x=0 ; $x<5 ; $x++){

	?>
	Does this work? <br/>


	<?php
}

?>

    <?php echo \safeTextObject($this->call("viewFunction", 5), ENT_QUOTES) ; ?>


    Basic test passed.
    

<?php 
    }



    function renderInternal() {
?>
 <?php $this->mainContent();  ?> 
<?php 
    }

    }

        ?>