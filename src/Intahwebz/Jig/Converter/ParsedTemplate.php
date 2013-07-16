<?php


namespace Intahwebz\Jig\Converter;

use Intahwebz\Utils\SafeAccess;


class ParsedTemplate {

    use SafeAccess;

    /**
     * @var string[]
     */
    private $textLines;

    private $localVariables = array();

    var $functionBlocks = array();

    private $className = null;

    var $extends = null;

    public $dynamicExtends = null;

    public $baseNamespace;

    public function __construct($baseNamespace){
        $this->baseNamespace = $baseNamespace;
    }

    function addTextLine($string){
        $this->textLines[] = $string;
    }


    /**
     * @param $className
     */
    public function setClassName($className){
        $className = str_replace("/", "\\", $className);
        $className = str_replace("-", "", $className);
        $this->className = $className;
    }

    /**
     * @return null
     */
    public function getClassName() {
        return $this->className;
    }

    /**
     * @return \string[]
     */
    function getLines() {
        return $this->textLines;
    }



    public function hasLocalVariable($variableName) {
        return in_array($variableName, $this->localVariables);
    }


    /**
     * @param $localVariable
     */
    public function addLocalVariable($localVariable){
        $varName = $localVariable;

        if(strpos($varName, '$') === 0) {
            $varName = substr($localVariable, 1);
        }

        if (in_array($varName, $this->localVariables) == false) {
            $this->localVariables[] = $varName;
        }
    }

    function addFunctionBlock($name, $block){
        $this->functionBlocks[$name] = $block;
    }

    /**
     * @return array
     */
    function getFunctionBlocks(){
        return $this->functionBlocks;
    }

    /**
     * @param $filename
     */
    public function setExtends($filename){
        //TODO allow full qualified names. Maybe.
        //$this->extends = "Intahwebz\\PHPCompiledTemplate\\".$filename;

        $this->extends = $filename;
    }

    /**
     * @param $filename
     */
    public function setDynamicExtends($filename) {
        $this->dynamicExtends = $filename;
    }

    /**
     * @return string
     */
    public function getParentClass() {
        if ($this->extends == null) {
            return "Intahwebz\\Jig\\JigBase";
        }

        $extendsClassName = str_replace('/', '\\', $this->extends);

        return $this->baseNamespace."\\".$extendsClassName;
    }

    /**
     * @return null
     */
    function getExtends(){
        return $this->extends;
    }


}



?>