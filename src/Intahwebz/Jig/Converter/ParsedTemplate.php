<?php


namespace Intahwebz\Jig\Converter;

use Intahwebz\Jig\JigException;
use Intahwebz\Utils\SafeAccess;


class ParsedTemplate {

    const jigExtension = '';
    
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

        $this->className = $className.self::jigExtension;
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

        //echo "hmm - this may be broken";
        return $this->baseNamespace."\\".$extendsClassName;
    }

    /**
     * @return null
     */
    function getExtends(){
        return $this->extends;
    }

    /**
     * @param $compilePath
     * @param $proxied
     * @return string
     * @throws \Intahwebz\Jig\JigException
     */
    function saveCompiledTemplate($compilePath, $proxied) {
        $fullClassName = $this->baseNamespace."\\".$this->getClassName();
        $fullClassName = str_replace("/", "\\", $fullClassName);

        $namespace = getNamespace($fullClassName);
        $className = getClassName($fullClassName);

        if($proxied == true) {
            $parentFullClassName =	$fullClassName;
            $className = 'Proxied'.$className;
        }
        else if ($this->dynamicExtends != null) {
            //Dynamic extension does class extension at run time.
            $parentFullClassName = "\\Intahwebz\\Jig\\DynamicTemplateExtender";
        }
        else{
            $parentFullClassName = $this->getParentClass();
        }

        $parentFullClassName = str_replace("/", "\\", $parentFullClassName);

        $outputFilename = convertNamespaceClassToFilepath($namespace."\\".$className);
        $outputFilename = $compilePath.$outputFilename.".php";

        ensureDirectoryExists($outputFilename);

        $outputFileHandle = @fopen($outputFilename, "w");

        if ($outputFileHandle == false) {
            throw new \Intahwebz\Jig\JigException("Could not open file [$outputFilename] for writing template.");
        }

        $parentClassName = getClassName($parentFullClassName);

        $startSection = <<< END
<?php

namespace $namespace;

use $parentFullClassName;

class $className extends $parentClassName {

END;

        fwrite($outputFileHandle, $startSection);

        if ($this->dynamicExtends != null) {
            $this->writeMappedSection($outputFileHandle);
        }

        if($proxied == true) {
            $this->writeProxySection($outputFileHandle);
        }
        else {
            $functionBlocks = $this->getFunctionBlocks();

            foreach ($functionBlocks as $name => $functionBlockSegments) {
                $this->writeFunction($outputFileHandle, $name, $functionBlockSegments);
            }
        }

        if ($this->getExtends() == null &&
            $this->dynamicExtends == null &&
            $proxied == false) {
            $remainingSegments = $this->getLines();
            $this->writeFunction($outputFileHandle, 'renderInternal', $remainingSegments);
        }

        $this->writeEndSection($outputFileHandle);

        fclose($outputFileHandle);

        return $outputFilename;
    }

    /**
     * @param $outputFileHandle
     */
    function writeEndSection($outputFileHandle) {

        $endSection = <<< END
		}

		?>
END;

        fwrite($outputFileHandle, $endSection);
    }

    /**
     * @param $outputFileHandle
     * @param $functionName
     * @param $lines
     */
    function writeFunction($outputFileHandle, $functionName, $lines) {
        if ($lines > 0) {
            fwrite($outputFileHandle, "\n");
            fwrite($outputFileHandle, "\n");
            fwrite($outputFileHandle, "\tfunction ".$functionName."() {\n");
            fwrite($outputFileHandle, "?>\n");
            fwrite($outputFileHandle, "\n");

            foreach ($lines as $line) {
                fwrite($outputFileHandle, $line);
            }

            fwrite($outputFileHandle, "\n");
            fwrite($outputFileHandle, "<?php \n");
            fwrite($outputFileHandle, "\t}\n");
            fwrite($outputFileHandle, "\n");
        }
    }

    /**
     * @param $outputFileHandle
     */
    function writeMappedSection($outputFileHandle) {

        $dynamicExtends = $this->dynamicExtends;

        //Todo just pass in parent class namen - or eve just parent instance
$output = <<< END
public function __construct(\$viewModel, \$jigRender) {
    \$this->viewModel = \$viewModel;
    \$this->jigRender = \$jigRender;
    \$classInstanceName = \$jigRender->getProxiedClass('$dynamicExtends');
    //\$fullclassName = "\\\\Intahwebz\\\\PHPCompiledTemplate\\\\".\$classInstanceName;
    \$fullclassName = \$classInstanceName;

    \$parentInstance = new \$fullclassName(\$viewModel, \$jigRender, \$this);
    \$this->setParentInstance(\$parentInstance);
}
END;

        fwrite($outputFileHandle, "\n");
        fwrite($outputFileHandle, "\n");
        fwrite($outputFileHandle, $output);
        fwrite($outputFileHandle, "\n");
        fwrite($outputFileHandle, "\n");
    }


    /**
     * @param $outputFileHandle
     */
    public function writeProxySection($outputFileHandle) {

        fwrite($outputFileHandle, "\n");
        $output = <<< END

		var \$childInstance = null;
		var \$viewModel = null;
		var \$jigRender = null; 

		function __construct(\$viewModel, \$jigRender, \$childInstance){
			\$this->viewModel = \$viewModel;
			\$this->jigRender = \$jigRender;
			\$this->childInstance = \$childInstance;
		}
END;

        fwrite($outputFileHandle, "\n");
        fwrite($outputFileHandle, $output);
        fwrite($outputFileHandle, "\n");

        $functionBlocks = $this->getFunctionBlocks();
        foreach ($functionBlocks as $name => $functionBlockSegments) {

            $output = <<< END

			function $name() {
				if (method_exists (\$this->childInstance, '$name') == true) {
					return \$this->childInstance->$name();
				}
				parent::$name();
			}
END;

            fwrite($outputFileHandle, "\n");
            fwrite($outputFileHandle, $output);
            fwrite($outputFileHandle, "\n");
        }

        fwrite($outputFileHandle, "\n");
    }



}


