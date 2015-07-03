<?php


namespace Jig\Converter;

use Jig\JigException;

function convertTypeToParam($helper)
{
    $helper = str_replace('\\', '_', $helper);

    return $helper;
}

class ParsedTemplate
{
    
    /**
     * @var string[]
     */
    private $textLines;

    private $localVariables = array();

    private $functionBlocks = array();

    private $className = null;

    private $extends = null;

    private $baseNamespace;

    private $injections = array();
    
    private $helpers = array();
    
    private $filters = array();
    
    private $renderBlocks = array(); 
    
    private $includeFiles = array();

    public function __construct($baseNamespace)
    {
        $this->baseNamespace = $baseNamespace;
    }

    public function addTextLine($string)
    {
        $this->textLines[] = $string;
    }

    public function addInjection($name, $value)
    {
        $this->injections[$name] = $value;
    }

    public function addHelper($name)
    {
        $this->helpers[] = $name;
    }
    
    public function addFilter($filterClassname)
    {
        $this->filters[] = $filterClassname;
    }

    public function addRenderBlock($renderBlockClassname)
    {
        $this->renderBlocks[] = $renderBlockClassname;
    }

    public function getRenderBlockClassnames()
    {
        return $this->renderBlocks;
    }
    
    public function getFilters()
    {
        return $this->filters;
    }

    private function callStaticInfoMethod($classnames, $methodName)
    {
        $knownItems = [];

        foreach ($classnames as $classname) {
            try {
                $reflection = new \ReflectionMethod($classname, $methodName);
                if ($reflection->isStatic() == false) {
                    throw new JigException(
                        "Method $methodName for filter class $classname must be static.",
                        JigException::FILTER_NO_INFO
                    );
                }

                $closure = $reflection->getClosure(null);
                $filters = $closure();
                if (is_array($filters) == false) {
                    throw new JigException(
                        "Method $methodName for class $classname must return an array of the names, and the names must be strings",
                        JigException::FILTER_NO_INFO
                    );
                }

                foreach ($filters as $filter) {
                    if (is_string($filter) == false) {
                        throw new JigException(
                            "Method getFilterList for filter class $classname must return an array of the names of filters, and the names must be strings",
                            JigException::FILTER_NO_INFO
                        );
                    }
                }

                //TODO - should we detect duplicate filters here?
                $knownItems = array_merge($knownItems, $filters);
            }
            catch (\ReflectionException $re) {
                throw new JigException(
                    "Class $classname does not have a static method $methodName",
                    JigException::FILTER_NO_INFO,
                    $re
                );
            }
        }

        return $knownItems;
    }
    
    /**
     * @return array
     * @throws JigException
     */
    public function getKnownFilters()
    {
        return $this->callStaticInfoMethod($this->filters, 'getFilterList');
    }
    
    public function getKnownRenderBlocks()
    {
        return $this->callStaticInfoMethod($this->renderBlocks, 'getBlockList');
    }

    public function addIncludeFile($filename, $paramName, $className)
    {
        $this->addInjection($paramName, $className);
        $this->includeFiles[] = $filename;
    }

    /**
     * @param $className
     */
    public function setClassName($className)
    {
        $className = str_replace("/", "\\", $className);
        $className = str_replace("-", "", $className);

        $this->className = $className;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return \string[]
     */
    public function getLines()
    {
        return $this->textLines;
    }

    /**
     * @param $variableName
     * @return bool
     */
    public function hasLocalVariable($variableName)
    {
        return in_array($variableName, $this->localVariables);
    }

    /**
     * Add a local variable so that any usage of it doesn't
     * trigger trying to fetch it from the ViewModel
     * @param $localVariable
     */
    public function addLocalVariable($localVariable)
    {
        $varName = $localVariable;

        if (strpos($varName, '$') === 0) {
            $varName = substr($localVariable, 1);
        }

        if (in_array($varName, $this->localVariables) == false) {
            $this->localVariables[] = $varName;
        }
    }

    /**
     * @param $name
     * @param $block
     */
    public function addFunctionBlock($name, $block)
    {
        $this->functionBlocks[$name] = $block;
    }

    /**
     * @return array
     */
    public function getFunctionBlocks()
    {
        return $this->functionBlocks;
    }

    /**
     * @param $filename
     */
    public function setExtends($filename)
    {
        $this->extends = $filename;
    }

    /**
     * @return string
     */
    public function getParentClass()
    {
        if ($this->extends == null) {
            return "Jig\\JigBase";
        }
        $extendsClassName = str_replace('/', '\\', $this->extends);
        
        return \Jig\getFQCN($this->baseNamespace, $extendsClassName);
    }

    /**
     * @return null|string
     */
    public function getExtends()
    {
        return $this->extends;
    }
    
    public function getTemplateDependencies()
    {
        $dependencies = [];

        if ($this->extends) {
            $dependencies[] = $this->extends;
        }

        $dependencies = array_merge($dependencies, $this->includeFiles);

        return $dependencies;
    }

    /**
     * @param $compilePath
     * @return string
     * @throws \Jig\JigException
     */
    public function saveCompiledTemplate($compilePath)
    {
        $fullClassName = \Jig\getFQCN($this->baseNamespace, $this->getClassName());
        $fullClassName = str_replace("/", "\\", $fullClassName);

        $namespace = \Jig\getNamespace($fullClassName);
        $className = \Jig\getClassName($fullClassName);
        $parentFullClassName = $this->getParentClass();
        $parentFullClassName = str_replace("/", "\\", $parentFullClassName);

        $outputFilename = \Jig\convertNamespaceClassToFilepath($namespace."\\".$className);
        $outputFilename = $compilePath.$outputFilename.".php";

        \Jig\ensureDirectoryExists($outputFilename);

        $directoryName = dirname($outputFilename);
        $tempFilename = tempnam($directoryName, 'jig');
        chmod($tempFilename, 0750);
        $outputFileHandle = @fopen($tempFilename, "w");

        if ($outputFileHandle == false) {
            throw new JigException("Could not open file [$outputFilename] for writing template.");
        }

        $parentClassName = \Jig\getClassName($parentFullClassName);

        $namespaceString = '';
        if (strlen(trim($namespace))) {
            $namespaceString = "namespace $namespace;";
        }

        $startSection = <<< END
<?php

$namespaceString

use $parentFullClassName;
//use Jig\JigRender;

class $className extends $parentClassName {

END;

        $parentDependencies = call_user_func([$parentFullClassName, 'getDependencyList']); 

        // TODO - check no clashes on names.
        fwrite($outputFileHandle, $startSection);

        $this->writeProperties($outputFileHandle);
        $this->writeConstructor($outputFileHandle, $parentDependencies);
        $this->writeDependencyList($outputFileHandle);

        $functionBlocks = $this->getFunctionBlocks();

        foreach ($functionBlocks as $name => $functionBlockSegments) {
            $this->writeFunction($outputFileHandle, $name, $functionBlockSegments);
        }

        if ($this->getExtends() == null) {
            $remainingSegments = $this->getLines();
            $this->writeFunction($outputFileHandle, 'renderInternal', $remainingSegments);
        }

        $this->writeEndSection($outputFileHandle);

        //Close the file and move it to the correct place atomically.
        fclose($outputFileHandle);
        $renameResult = rename($tempFilename, $outputFilename);
        if (!$renameResult) {
            throw new JigException("Failed to rename temp file $tempFilename to $outputFilename");
        }

        return $outputFilename;
    }

    /**
     * @param $outputFileHandle
     */
    public function writeEndSection($outputFileHandle)
    {
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
    public function writeFunction($outputFileHandle, $functionName, $lines)
    {
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
    public function writeInjectionArray($outputFileHandle)
    {
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

    /**
     * @param $outputFileHandle
     */
    public function writeProperties($outputFileHandle)
    {
        $output = '';

        foreach ($this->injections as $name => $type) {
            $output .=  "\n    private \$$name;";
        }

        fwrite($outputFileHandle, $output);
    }


    /**
     * @param $outputFileHandle
     */
    public function writeConstructor($outputFileHandle, $parentDependencies)
    {
        $depdendencies = '';
        $separator = "";

        $fullDependencies = array_merge($this->injections, $parentDependencies);

        foreach ($fullDependencies as $name => $type) {
            $depdendencies .= $separator."       \\$type \$$name";
            $separator = ",\n";
        }

        foreach ($this->helpers as $helper) {
            $helperParam = convertTypeToParam($helper);
            $depdendencies .= $separator."       \\$helper \$$helperParam";
            $separator = ",\n";
        }

        foreach ($this->filters as $filter) {
            $filterParam = convertTypeToParam($filter);
            $depdendencies .= $separator."       \\$filter \$$filterParam";
            $separator = ",\n";
        }

        foreach ($this->renderBlocks as $renderBlock) {
            $renderBlockParam = convertTypeToParam($renderBlock);
            $depdendencies .= $separator."       \\$renderBlock \$$renderBlockParam";
            $separator = ",\n";
        }

        $output = "
    function __construct(
$depdendencies
    )
    {
        //\$this->jigRender = \$jigRender;
";
        
               //JigRender \$jigRender

        foreach ($this->injections as $name => $type) {
            $output .=  "        \$this->$name = \$$name;\n";
        }

        foreach ($this->helpers as $helper) {
            $helperParam = convertTypeToParam($helper);
            $output .=  "        \$this->addTemplateHelper(\$$helperParam);\n";
        }

        foreach ($this->filters as $filter) {
            $filterParam = convertTypeToParam($filter);
            $output .=  "        \$this->addFilter(\$$filterParam);\n";
        }

        foreach ($this->renderBlocks as $renderBlock) {
            $renderParam = convertTypeToParam($renderBlock);
            $output .=  "        \$this->addRenderBlock(\$$renderParam);\n";
        }
        
        if (count($parentDependencies)) {
            $output .=  "        
        parent::__construct(\n";
            $separator = '';
            foreach ($parentDependencies as $name => $type) {
                $output .=  $separator."\$$name";
                $separator = ",\n            ";
            }
            $output .=  
"//            \$jigRender
        );\n";  
        }

        $output .=  "    }\n";

        fwrite($outputFileHandle, "\n");
        fwrite($outputFileHandle, $output);
    }

    /**
     * @param $outputFileHandle
     */
    function writeDependencyList($outputFileHandle)
    {
        $output = "
    public static function getDependencyList() {
    
        return [\n";
        
        foreach ($this->injections as $name => $type) {
            $output .=  "            '$name' => '$type',\n";
        }

        foreach ($this->helpers as $type) {
            $name = convertTypeToParam($type);
            $output .=  "            '$name' => '$type',\n";
        }
        
        foreach ($this->filters as $type) {
            $name = convertTypeToParam($type);
            $output .=  "            '$name' => '$type',\n";
        }
        
        foreach ($this->renderBlocks as $type) {
            $name = convertTypeToParam($type);
            $output .=  "            '$name' => '$type',\n";
        }

        $output .= "        ];
    }
        ";

        fwrite($outputFileHandle, "\n");
        fwrite($outputFileHandle, $output);
        fwrite($outputFileHandle, "\n");
    }
}
