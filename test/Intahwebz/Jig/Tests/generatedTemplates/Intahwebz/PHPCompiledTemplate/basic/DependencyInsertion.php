<?php

namespace Intahwebz\PHPCompiledTemplate\basic;

use Intahwebz\Jig\JigBase;

class DependencyInsertion extends JigBase {

    private $injections = array(
        'navLinks' => 'Intahwebz\Jig\Tests\SiteNavLinks'
    );

    protected $navLinks;


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





<?php foreach ( $this->getVariable("navLinks") as $navLink){  ?>
    navlink is <?php echo \safeTextObject($navLink['url'], ENT_QUOTES) ; ?> => <?php echo \safeTextObject($navLink['description'], ENT_QUOTES) ; ?><br/>
<?php  }  ?>


<?php 
    }

    }

        ?>