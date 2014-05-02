<?php

namespace Intahwebz\PHPCompiledTemplate\dynamicExtend;

use \Intahwebz\Jig\DynamicTemplateExtender;

class dynamicChild extends DynamicTemplateExtender {

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




public function __construct($jigRender, $viewModel) {
    $this->viewModel = $viewModel;
    $this->jigRender = $jigRender;
    $classInstanceName = $jigRender->getProxiedClass('parent');
    //$fullclassName = "\\Intahwebz\\PHPCompiledTemplate\\".$classInstanceName;
    $fullclassName = $classInstanceName;

    $parentInstance = new $fullclassName($jigRender, $viewModel, $this);
    $this->setParentInstance($parentInstance);
}



    function content() {
?>
    
    This is the child content.


    <?php echo \safeTextObject($this->call("testCallableFunction"), ENT_QUOTES) ; ?>
    

<?php 
    }

    }

        ?>