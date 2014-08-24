<?php


namespace Jig\Converter;

use Jig\JigException;





class ParsedTemplate {
    
    /**
     * @var string[]
     */
    private $textLines;

    private $localVariables = array();

    private $functionBlocks = array();

    private $className = null;

    private $extends = null;

    private $dynamicExtends = null;

    private $baseNamespace;

    private $injections = array();

    public function __construct($baseNamespace){
        $this->baseNamespace = $baseNamespace;
    }

    function addTextLine($string){
        $this->textLines[] = $string;
    }

    function  addInjection($name, $value) {
        $this->injections[$name] = $value;
    }

    function getDynamicExtends() {
        return $this->dynamicExtends;
    }

    /**
     * @param $className
     */
    public function setClassName($className) {
        $className = str_replace("/", "\\", $className);
        $className = str_replace("-", "", $className);

        $this->className = $className;
    }

    /**
     * @return string
     */
    function getClassName() {
        return $this->className;
    }

    /**
     * @return \string[]
     */
    function getLines() {
        return $this->textLines;
    }

    function hasLocalVariable($variableName) {
        return in_array($variableName, $this->localVariables);
    }

    /**
     * @param $localVariable
     */
    function addLocalVariable($localVariable) {
        $varName = $localVariable;

        if(strpos($varName, '$') === 0) {
            $varName = substr($localVariable, 1);
        }

        if (in_array($varName, $this->localVariables) == false) {
            $this->localVariables[] = $varName;
        }
    }

    function addFunctionBlock($name, $block) {
        $this->functionBlocks[$name] = $block;
    }

    /**
     * @return array
     */
    function getFunctionBlocks() {
        return $this->functionBlocks;
    }

    /**
     * @param $filename
     * @TODO allow full qualified names. Maybe.
     */
    function setExtends($filename) {
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
            return "Jig\\JigBase";
        }

        $extendsClassName = str_replace('/', '\\', $this->extends);

        //echo "hmm - this may be broken";
        return $this->baseNamespace."\\".$extendsClassName;
    }

    /**
     * @return null|string
     */
    function getExtends() {
        return $this->extends;
    }

    /**
     * @param $compilePath
     * @param $proxied
     * @return string
     * @throws \Jig\JigException
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
            $parentFullClassName = "\\Jig\\DynamicTemplateExtender";
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
            throw new \Jig\JigException("Could not open file [$outputFilename] for writing template.");
        }

        $parentClassName = getClassName($parentFullClassName);

        $startSection = <<< END
<?php

namespace $namespace;

use $parentFullClassName;

class $className extends $parentClassName {

END;

        fwrite($outputFileHandle, $startSection);

        $this->writeInjectionArray($outputFileHandle);
        $this->writeInjectionFunctions($outputFileHandle);

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
            fwrite($outputFileHandle, "    function ".$functionName."() {\n");
            fwrite($outputFileHandle, "?>");

            foreach ($lines as $line) {
                fwrite($outputFileHandle, $line);
            }

            fwrite($outputFileHandle, "\n");
            fwrite($outputFileHandle, "<?php \n");
            fwrite($outputFileHandle, "    }\n");
            fwrite($outputFileHandle, "\n");
        }
    }

    /**
     * @param $outputFileHandle
     */
    function writeMappedSection($outputFileHandle) {

        $dynamicExtends = $this->dynamicExtends;

        //Todo just pass in parent class name - or eve just parent instance
$output = <<< END
public function __construct(\$jigRender, \$viewModel) {
    \$this->viewModel = \$viewModel;
    \$this->jigRender = \$jigRender;
    \$classInstanceName = \$jigRender->getProxiedClass('$dynamicExtends');
    //\$fullclassName = "\\\\Jig\\\\PHPCompiledTemplate\\\\".\$classInstanceName;
    \$fullclassName = \$classInstanceName;

    \$parentInstance = new \$fullclassName(\$jigRender, \$viewModel, \$this);
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

		function __construct(\$jigRender, \$viewModel, \$childInstance){
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


    function writeInjectionArray($outputFileHandle) {

        $output = "    private \$injections = array(\n";
        $separator = '';

        foreach ($this->injections as $name => $value) {
            $output .= $separator;
            $output .= "        '$name' => '$value'\n";
            $separator = ',';
        }

        $output .= "    );\n\n";

        foreach ($this->injections as $name => $value) {
            $output .= "    protected \$$name;\n";
        }

        fwrite($outputFileHandle, "\n");
        fwrite($outputFileHandle, $output);
        fwrite($outputFileHandle, "\n");
    }

    function writeInjectionFunctions($outputFileHandle) {

        $output = "    function getInjections() {
            \$parentInjections = parent::getInjections();

            return array_merge(\$parentInjections, \$this->injections);
        }\n\n";


        $output .= "   function getVariable(\$name) {
            if (property_exists(\$this, \$name) == true) {
                return \$this->{\$name};
            }

            return parent::getVariable(\$name);
        }\n\n";

        fwrite($outputFileHandle, "\n");
        fwrite($outputFileHandle, $output);
        fwrite($outputFileHandle, "\n");
    }
}


