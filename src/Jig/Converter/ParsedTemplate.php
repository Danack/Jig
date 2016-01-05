<?php


namespace Jig\Converter;

use Jig\JigException;

class ParsedTemplate
{
    /**
     * @var string[]
     */
    private $textLines;

    private $localVariables = array();

    private $functionBlocks = array();

    private $templateName = null;

    private $extends = null;

    private $baseNamespace;

    private $injections = array();
    
    private $plugins = array();
    
    private $includeFiles = array();
    
    private $templatesUsed = array();

    public function __construct($baseNamespace, $defaultPlugins)
    {
        $this->baseNamespace = $baseNamespace;
        $this->plugins = $defaultPlugins;
    }

    /**
     * @return array
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    public function addTextLine($string)
    {
        $this->textLines[] = $string;
    }

    public function addInjection($name, $type)
    {
        if (array_key_exists($name, $this->injections) === true) {
            if (strcasecmp($type, $this->injections[$name]) !== 0) {
                $message = sprintf(
                    "Cannot inject type %s as name %s, it is already injected as type %s",
                    $type,
                    $name,
                    $this->injections[$name]
                );
                throw new JigException(
                    $message,
                    JigException::INJECTION_ERROR
                );
            }
        }
        
        $this->injections[$name] = $type;
    }

    private function callStaticInfoMethod($classnames, $methodName)
    {
        $knownItems = [];

        foreach ($classnames as $classname) {
            $implementedInterfaces = class_implements($classname);

            if ($implementedInterfaces === false) {
                throw new JigException("Failed to load plugin class $classname to get info from it.");
            }
                
            if (in_array('Jig\Plugin', $implementedInterfaces) === false) {
                $message = "Class $classname does not implement interface Jig\\Plugin, cannot be used as a plugin.";
                throw new JigException($message);
            }

            $callable = [$classname, $methodName];

            $listItems = call_user_func($callable);
            if (is_array($listItems) === false) {
                $message = sprintf(
                    "Method %s for class %s must return an array of the names, and the names must be strings",
                    $methodName,
                    $classname
                );
                
                throw new JigException(
                    $message,
                    JigException::FILTER_NO_INFO
                );
            }

            foreach ($listItems as $item) {
                if (is_string($item) === false) {
                    $message = sprintf(
                        "Method %s for class %s must return an array of the names, and the names must be strings",
                        $methodName,
                        $classname
                    );
                    
                    throw new JigException(
                        $message,
                        JigException::FILTER_NO_INFO
                    );
                }
            }

            //TODO - should we detect and warn on duplicate filters here?
            $knownItems = array_merge($knownItems, $listItems);
        }

        return $knownItems;
    }
    
    public function getKnownRenderBlocks()
    {
        return $this->callStaticInfoMethod($this->plugins, 'getBlockRenderList');
    }

    public function addIncludeFile($filename, $paramName, $className)
    {
        $this->addInjection($paramName, $className);
        $this->includeFiles[] = $filename;
        $this->templatesUsed[] = $filename;
    }

    public function addPlugin($pluginClassname)
    {
        //TODO - validate $pluginClassname is a valid php classname
        $this->plugins[] = $pluginClassname;
    }

    /**
     * @param $className
     */
    public function setTemplateName($templateName)
    {
        $this->templateName = $templateName;
    }

    /**
     * @return string
     */
    public function getTemplateName()
    {
        return $this->templateName;
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

    public function checkVariableKnown($variableName)
    {
        if (in_array($variableName, $this->localVariables) === true) {
            return;
        }
        
        if (array_key_exists($variableName, $this->injections) === true) {
            return;
        }

        throw new JigException(
            "Unknown variable '$variableName'",
            \Jig\JigException::UNKNOWN_VARIABLE
        );
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

        if (in_array($varName, $this->localVariables) === false) {
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
        $this->templatesUsed[] = $filename;
    }

    /**
     * @return string
     */
    public function getParentClass()
    {
        if ($this->extends === null) {
            return "Jig\\JigBase";
        }
        $extendsClassName = str_replace('/', '\\', $this->extends);
        
        $extendsClassName .= "Jig";
        
        return self::getFQCN($this->baseNamespace, $extendsClassName);
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

        if ($this->extends !== null) {
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
    public function saveCompiledTemplate($compilePath, $fqcn)
    {
        $fullClassName = $fqcn;

        $namespace = self::getNamespace($fullClassName);
        $className = self::getClassName($fullClassName);
        $parentFullClassName = $this->getParentClass();
        $parentFullClassName = str_replace("/", "\\", $parentFullClassName);

        $outputFilename = str_replace('\\', "/", $fqcn);
        $outputFilename = $compilePath.$outputFilename.".php";

        self::ensureDirectoryExists($outputFilename);

        $directoryName = dirname($outputFilename);
        $tempFilename = tempnam($directoryName, 'jig');
        chmod($tempFilename, 0750);
        $outputFileHandle = @fopen($tempFilename, "w");

        if ($outputFileHandle === false) {
            throw new JigException("Could not open file [$outputFilename] for writing template.");
        }

        $parentClassName = self::getClassName($parentFullClassName);

        $namespaceString = '';
        if (strlen(trim($namespace)) !== 0) {
            $namespaceString = "namespace $namespace;";
        }

        $startSection = <<< END
<?php

$namespaceString

use $parentFullClassName;

class $className extends $parentClassName {

END;

        $parentDependencies = call_user_func([$parentFullClassName, 'getDependencyList']);

        // TODO - check no clashes on names.
        fwrite($outputFileHandle, $startSection);

        $this->writeProperties($outputFileHandle);
        $this->writeConstructor($outputFileHandle, $parentDependencies, $parentFullClassName);
        $this->writeTemplatesUsed($outputFileHandle);
        $this->writeDependencyList($outputFileHandle);

        $functionBlocks = $this->getFunctionBlocks();

        foreach ($functionBlocks as $name => $functionBlockSegments) {
            $this->writeFunction($outputFileHandle, $name, $functionBlockSegments);
        }

        if ($this->getExtends() === null) {
            $remainingSegments = $this->getLines();
            $this->writeFunction($outputFileHandle, 'renderInternal', $remainingSegments);
        }

        $this->writeEndSection($outputFileHandle);

        //Close the file and move it to the correct place atomically.
        fclose($outputFileHandle);
        $renameResult = rename($tempFilename, $outputFilename);
        if ($renameResult === false) {
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
            foreach ($lines as $line) {
                fwrite($outputFileHandle, $line);
            }

            //fwrite($outputFileHandle, "\nTEXT;\n");
            fwrite($outputFileHandle, "\n");
            fwrite($outputFileHandle, "    }\n");
            fwrite($outputFileHandle, "\n");
        }
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

        foreach (array_unique($this->plugins) as $plugin) {
            $name = self::convertTypeToParam($plugin);
            $output .=  "\n    private \$$name;";
        }

        fwrite($outputFileHandle, $output);
    }

    private function getFullDependencies($parentFullClassName)
    {
        $parentDependencies = call_user_func([$parentFullClassName, 'getDependencyList']);
        $fullDependencies = array_merge($parentDependencies, $this->injections);
        
        foreach (array_unique($this->plugins) as $pluginType) {
            $pluginParam = self::convertTypeToParam($pluginType);
            $fullDependencies[$pluginParam] = $pluginType;
        }
        
        return $fullDependencies;
    }
    
    /**
     * @param $outputFileHandle
     */
    public function writeConstructor($outputFileHandle, $parentDependencies, $parentFullClassName)
    {

        $depdendencies = '';
        $separator = "";

        $parentDependencies = call_user_func([$parentFullClassName, 'getDependencyList']);
        $fullDependencies = $this->getFullDependencies($parentFullClassName);

        foreach ($fullDependencies as $name => $type) {
            $depdendencies .= $separator."       \\$type \$$name";
            $separator = ",\n";
        }

        $functionString = <<< FUNCTION


    public function __construct(
        %s
    ) {
%s
        %s
    }
    
    
FUNCTION;

        $fullDependencyString = '';
        $separator = '';
        foreach ($fullDependencies as $name => $type) {
            $fullDependencyString .= $separator."\\$type \$$name";
            $separator = ",\n        ";
        }

        $selfAssignmentString = '';
        foreach ($this->injections as $name => $type) {
            $selfAssignmentString .=  "        \$this->$name = \$$name;\n";
        }

        foreach (array_unique($this->plugins) as $pluginType) {
            $pluginParam = self::convertTypeToParam($pluginType);
            $selfAssignmentString .=  "        \$this->$pluginParam = \$$pluginParam;\n";
            $selfAssignmentString .=  "        \$this->addPlugin(\$$pluginParam);\n";
        }

        $parentConstructString = '';
        if (count($parentDependencies) !== 0) {
            $parentConstructString .=  "parent::__construct(";
            $separator = '';
            foreach ($parentDependencies as $name => $type) {
                $parentConstructString .=  $separator."\n            \$$name";
                $separator = ",";
            }
            $parentConstructString .="\n        );\n";
        }

        $output = sprintf(
            $functionString,
            $fullDependencyString,
            $selfAssignmentString,
            $parentConstructString
        );

        fwrite($outputFileHandle, $output);
    }

    public function getRenderDependencies()
    {
        $dependencies = $this->injections;
        foreach ($this->plugins as $plugin) {
            $name = self::convertTypeToParam($plugin);
            //TODO - check already set
            $dependencies[$name] = $plugin;
        }
        
        return $dependencies;
    }
    
    /**
     * @param $outputFileHandle
     */
    public function writeDependencyList($outputFileHandle)
    {
        $functionString = <<< FUNCTION

    public static function getDependencyList()
    {
        \$parentDependencies = parent::getDependencyList();
        \$selfDependencies = [%s
        ];
        
        //TODO - check for clashes

        return array_merge(\$parentDependencies, \$selfDependencies);
    }

FUNCTION;

        $dependenciesString = '';
        $renderDependencies = $this->getRenderDependencies();
        foreach ($renderDependencies as $name => $type) {
            $dependenciesString .=  "\n            '$name' => '$type',";
        }

        fwrite($outputFileHandle, sprintf($functionString, $dependenciesString));
    }
    
    public function writeTemplatesUsed($outputFileHandle)
    {
        $output = "
    public static function getTemplatesUsed() {

        return [\n";

        foreach ($this->templatesUsed as $name) {
            $output .=  "            '$name',\n";
        }

        $output .= "        ];
    }
        ";
        fwrite($outputFileHandle, "\n");
        fwrite($outputFileHandle, $output);
        fwrite($outputFileHandle, "\n");
    }
    
    public static function convertTypeToParam($helper)
    {
        $helper = str_replace('\\', '_', $helper);
    
        return $helper;
    }
    
    /**
     * Get the name space part of a fully namespaced class. Returns empty string
     * if the class had no namespace part.
     * @param $namespaceClass
     * @return string
     */
    public static function getNamespace($namespaceClass)
    {
        if (is_object($namespaceClass) === true) {
            $namespaceClass = get_class($namespaceClass);
        }
    
        $lastSlashPosition = mb_strrpos($namespaceClass, '\\');
    
        if ($lastSlashPosition !== false) {
            return mb_substr($namespaceClass, 0, $lastSlashPosition);
        }
    
        return "";
    }

    public static function getFQCN($namespace, $classname)
    {
        if (strlen($namespace) !== 0) {
            return $namespace."\\".$classname;
        }
    
        return $classname;
    }
    
    /**
     * Get the class part of a fully namespaced class name
     * @param $namespaceClass
     * @return string
     */
    public static function getClassName($namespaceClass)
    {
        $lastSlashPosition = mb_strrpos($namespaceClass, '\\');
    
        if ($lastSlashPosition !== false) {
            return mb_substr($namespaceClass, $lastSlashPosition + 1);
        }
    
        return $namespaceClass;
    }
    
    /**
     * ensureDirectoryExists by creating it with 0755 permissions and throwing
     * an exception if it does not exst after that mkdir call.
     * @param $outputFilename
     * @throws JigException
     */
    public function ensureDirectoryExists($outputFilename)
    {
        $directoryName = dirname($outputFilename);
        @mkdir($directoryName, 0755, true);
        
        //TODO - double-check umask
    
        if (file_exists($directoryName) === false) {
            throw new JigException("Directory $directoryName does not exist and could not be created");
        }
    }
}
