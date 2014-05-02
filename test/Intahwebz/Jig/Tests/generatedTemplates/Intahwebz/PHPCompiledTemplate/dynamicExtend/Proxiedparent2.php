<?php

namespace Intahwebz\PHPCompiledTemplate\dynamicExtend;

use Intahwebz\PHPCompiledTemplate\dynamicExtend\parent2;

class Proxiedparent2 extends parent2 {

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





		var $childInstance = null;
		var $viewModel = null;
		var $jigRender = null; 

		function __construct($jigRender, $viewModel, $childInstance){
			$this->viewModel = $viewModel;
			$this->jigRender = $jigRender;
			$this->childInstance = $childInstance;
		}


            function content() {
                if (method_exists ($this->childInstance, 'content') == true) {
                    return $this->childInstance->content();
                }
                parent::content();
            }

    }

        ?>