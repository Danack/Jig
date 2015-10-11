<?php


namespace Jig\Converter;

use Jig\JigException;
use Jig\JigConfig;
use Jig\JigRender;

function convertClassnameToParam($classname)
{
    return str_replace('\\', '_', $classname);
}

/**
 * Class JigConverter The class that actually converts templates into PHP.
 * Only invoked during compilation.
 */
class JigConverter
{
    /**
     * @var \Jig\JigConfig
     */
    private $jigConfig;

    const MODE_CODE = 0;
    const MODE_TEMPLATE = 1;

    const FILENAME_PATTERN = '[\.\w\\/]+';

    //Suppress escaping HTML output
    const FILTER_NONE = 'nofilter';

    // Don't output any return from the function
    const FILTER_NO_OUTPUT = 'nooutput';

    // Suppress adding ";" to generated code to allow modification
    // of the generated code.
    const FILTER_NO_PHP = 'nophp';
    
    const FILTER_HTML = 'html';
        
    const FILTER_JS = 'js';
    
    const FILTER_CSS = 'css';
    
    const FILTER_URL = 'url';

    const FILTER_HTML_ATTR = 'html_attr';

    const LITERAL_TEMPLATE = 'template';
    
    const LITERAL_PHP = 'php';
    
    /**
     * Is the converter currently in literal mode.
     * @var bool
     */
    private $literalMode = false;

    /**
     * @var array
     */
    private $compileBlockFunctions = array();

    /**
     * @var ParsedTemplate
     */
    private $parsedTemplate = null;

    /**
     * @var null|array Holds the lines in a block, so that they can be processed
     * at the end of the block.
     */
    private $activeBlock = null;

    /**
     * @var null|string The name of the active block if there is one.
     */
    private $activeBlockName = null;

    /**
     * @var array
     */
    private $defaultHelpers = [];

    /**
     * @var array
     */
    private $defaultFilters = [];

    /**
     * @var array
     */
    private $defaultRenderBlocks = [];

    /**
     * @var array
     */
    private $defaultPlugins = [];
    
    
    public static $builtinFilters = [
        self::FILTER_NONE,
        self::FILTER_NO_OUTPUT,
        self::FILTER_NO_PHP,
        self::FILTER_HTML,
        self::FILTER_JS,
        self::FILTER_CSS,
        self::FILTER_URL,
        self::FILTER_HTML_ATTR,
    ];
    
    /**
     * @param JigConfig $jigConfig
     */
    public function __construct(JigConfig $jigConfig)
    {
        $this->jigConfig = $jigConfig;
        $this->outputMode = self::MODE_TEMPLATE;
    }

    /**
     * @param $name
     */
    public function addDefaultHelper($name)
    {
        $this->defaultHelpers[] = $name;
    }

    public function addDefaultFilter($name)
    {
        $this->defaultFilters[] = $name;
    }

    public function addDefaultRenderBlock($name)
    {
        $this->defaultRenderBlocks[] = $name;
    }
    
    public function addDefaultPlugin($name)
    {
        $this->defaultPlugins[] = $name;
    }
    
    public function changeOutputMode($newOutputMode)
    {
        if ($newOutputMode == $this->outputMode) {
            return;
        }
        
        if ($newOutputMode == self::MODE_CODE) {
            $this->addLineInternal("TEXT;\n");
        }
        else if ($newOutputMode == self::MODE_TEMPLATE) {
            $this->addLineInternal("\n    echo <<< 'TEXT'\n");
        }
    
        $this->outputMode = $newOutputMode;
    }
    
    /**
     * @param $blockName
     * @return null|callable
     */
    public function matchCompileBlockFunction($segmentText)
    {
        foreach ($this->compileBlockFunctions as $blockName => $blockFunctions) {
            if (strncmp($segmentText, $blockName, mb_strlen($blockName)) == 0) {
                return $blockFunctions;
            }
        }

        return null;
    }

    /**
     * @param $blockName
     * @return null|callable
     */
    public function doesRenderBlockExist($blockName)
    {
        $knownRenderBlocks = $this->parsedTemplate->getKnownRenderBlocks();

        if (in_array($blockName, $knownRenderBlocks, true)) {
            return true;
        }

        return false;
    }

    /**
     * @param $fileLines
     * @throws \Exception
     * @return ParsedTemplate
     */
    public function createFromLines($fileLines)
    {
        if ($this->parsedTemplate != null) {
            throw new \Exception("Trying to convert template while in the middle of converting another one.");
        }

        $this->parsedTemplate = new ParsedTemplate(
            $this->jigConfig->compiledNamespace,
            $this->defaultPlugins
        );

        foreach ($fileLines as $fileLine) {
            $nextSegments = $this->getLineSegments($fileLine);

            foreach ($nextSegments as $segment) {
                $this->addSegment($segment);
            }
        }

        $parsedTemplate = $this->parsedTemplate;
        $this->parsedTemplate = null;

        return $parsedTemplate;
    }

    /**
     * @param $fileLine
     * @return TemplateSegment[]
     */
    public function getLineSegments($fileLine)
    {
        $segments = array();
        $matches = array();

        $commentStart = strpos($fileLine, "{*");
        if ($commentStart) {
            $commentEnd = strpos($fileLine, "*}", $commentStart);
            
            if ($commentEnd) {
                $segments[] = new CommentTemplateSegment(substr($fileLine, $commentStart, $commentEnd));
                $newLine = substr($fileLine, 0, $commentStart).substr($fileLine, $commentEnd + 2);
                $fileLine = $newLine;
            }
        }

        //U = ungreedy
        //u = utf
        $pattern = '/\{([^\s]+.*[^\s]+)\}/Uu';
        //TODO preg is the wrong way of doing this.

//http://stackoverflow.com/questions/524548/regular-expression-to-de
// tect-semi-colon-terminated-c-for-while-loops/524624#524624

/*
//        You could write a little, very simple routine that does it, without using a regular
 expression:
//
//Set a position counter pos so that is points to just before the opening bracket after your for
 or while.
//Set an open brackets counter openBr to 0.
//Now keep incrementing pos, reading the characters at the respective positions, and increment
 openBr when you see an opening bracket, and decrement it when you see a closing bracket. That
 will increment it once at the beginning, for the first opening bracket in "for (", increment and
 decrement some more for some brackets in between, and set it back to 0 when your for bracket
 closes.
//        So, stop when openBr is 0 again.
*/

        $matchCount = preg_match_all($pattern, $fileLine, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE);

        if ($matchCount == 0) {
            $segments[] = new TextTemplateSegment($fileLine);
        }
        else {
            $position = 0;

            foreach ($matches as $matchInfo) {
                $codeStartPosition = $matchInfo[0][1];

                if ($codeStartPosition > $position) {
                    $beforeText = mb_substr($fileLine, $position, $codeStartPosition - $position);
                    $segments[] = new TextTemplateSegment($beforeText);
                }

                $codeWithBrackets = $matchInfo[0][0];
                $code = $matchInfo[1][0];

                $startStr = mb_substr($codeWithBrackets, 0, 2);
                $endStr = mb_substr($codeWithBrackets, -2);

                $position = $codeStartPosition + mb_strlen($codeWithBrackets);

                if ($startStr== '{*' &&
                    $endStr == '*}') {
                    //it was a comment like {* *}
                    $segments[] = new CommentTemplateSegment($code);
                    continue;
                }

                $segments[] = new PHPTemplateSegment($code);
            }

            $remainingString = mb_substr($fileLine, $position);

            if ($remainingString !== false) {
                $segments[] = new TextTemplateSegment($remainingString);
            }
        }

        return $segments;
    }

    /**
     * @param $literalMode
     */
    public function setLiteralMode($literalMode)
    {
        $this->literalMode = $literalMode;
    }


    /**
     * @param $filename
     */
    public function setInclude($filename)
    {
        $className = $this->jigConfig->getFullClassname($filename);
        $paramName = convertClassnameToParam($className);

        $this->parsedTemplate->addIncludeFile($filename, $paramName, $className);
        
        $code = "echo \$this->".$paramName."->render();";
        $this->addCode($code);
    }

    /**
     * @param TemplateSegment $segment
     * @param JigRender $jigRender
     * @throws JigException
     */
    public function addSegment(TemplateSegment $segment)
    {
        $segmentText = $segment->text;

        if ($this->literalMode == self::LITERAL_TEMPLATE &&
            strncmp($segmentText, '/literal', mb_strlen('/literal')) == 0) {
            $this->processLiteralEnd();
            return;
        }
        
        if ($this->literalMode == self::LITERAL_PHP &&
            strncmp($segmentText, '/php', mb_strlen('/php')) == 0) {
            $this->processPHPEnd();
            $this->processLiteralEnd();
            return;
        }

        foreach ($this->compileBlockFunctions as $blockName => $blockFunctions) {
            if (strncmp($segmentText, '/'.$blockName, mb_strlen('/'.$blockName)) == 0) {
                call_user_func($blockFunctions[1], $this, $segmentText);
                return;
            }
        }
        
        if (preg_match('#/\w*#', $segmentText)) {
            $blockName = substr($segmentText, 1);
            $knownBlocks = $this->parsedTemplate->getKnownRenderBlocks();
            if (in_array($blockName, $knownBlocks, true)) {
                $this->addCode("\$this->endRenderBlock('$blockName');");
                return;
            }
        }

        //Anything that exits literal mode (i.e. /literal or /php) must be above this
        if ($this->literalMode) {
            $this->addLineInternal($segment->getRawString());
            return;
        }


        if ($segment instanceof TextTemplateSegment) {
            $this->changeOutputMode(self::MODE_TEMPLATE);
            $this->addLineInternal($segment->getString($this->parsedTemplate));
            $this->addLineInternal("\n");
        }
        else if ($segment instanceof PHPTemplateSegment) {
            $this->parseJigSegment($segment);
        }
        else if ($segment instanceof CommentTemplateSegment) {
            $this->addCode($segment->getString($this->parsedTemplate));
        }
        else {
            throw new \Jig\JigException("Unknown Segment type ".get_class($segment));
        }
    }

    /**
     * @param TemplateSegment $segment
     * @param JigRender $jigRender
     * @throws JigException
     */
    public function parseJigSegment(TemplateSegment $segment)
    {
        $segmentText = $segment->text;

        try {
            if (strncmp($segmentText, 'extends ', mb_strlen('extends ')) == 0) {
                $this->processExtends($segmentText);
            }
            else if (strncmp($segmentText, 'inject ', mb_strlen('inject ')) == 0) {
                $this->processInject($segmentText);
            }
            else if (strncmp($segmentText, 'plugin ', mb_strlen('plugin ')) == 0) {
                $this->processPlugin($segmentText);
            }
            else if (strncmp($segmentText, 'include ', mb_strlen('include ')) == 0) {
                $this->processInclude($segmentText);
            }
            else if (strncmp($segmentText, 'block ', mb_strlen('block ')) == 0) {
                $this->changeOutputMode(self::MODE_CODE);
                $this->processBlockStart($segmentText);
                //Blocks start in template mode.
                $this->outputMode = self::MODE_TEMPLATE;
            }
            else if (strncmp($segmentText, '/block', mb_strlen('/block')) == 0) {
                $this->processBlockEnd();
            }
            else if (strncmp($segmentText, 'foreach', mb_strlen('foreach')) == 0) {
                $this->processForeachStart($segmentText);
            }
            else if (strncmp($segmentText, '/foreach', mb_strlen('/foreach')) == 0) {
                $this->processForeachEnd();
            }
            else if (strncmp($segmentText, 'php', mb_strlen('php')) == 0) {
                $this->processPHPStart($segmentText);
            }
            else if (strncmp($segmentText, 'literal', mb_strlen('literal')) == 0) {
                $this->processLiteralStart();
            }
            else if (strncmp($segmentText, 'if ', mb_strlen('if ')) == 0) {
                $segment->text = substr($segmentText, 3);
                $text = $segment->getString($this->parsedTemplate, ['nofilter', 'nophp', 'nooutput']);
                $this->changeOutputMode(self::MODE_CODE);
                $this->addLineInternal('if ('.$text.') {');
                //$this->changeOutputMode(self::MODE_TEMPLATE);
            }
            else if (strncmp($segmentText, '/if', mb_strlen('/if')) == 0) {
                $this->changeOutputMode(self::MODE_CODE);
                $this->addLineInternal(' }');
                //$this->changeOutputMode(self::MODE_TEMPLATE);
            }
            else if (strncmp($segmentText, 'else', mb_strlen('else')) == 0) {
                $this->addCode(" } else { ");
            }
            else {
                $blockFunctionName = $segmentText;
                $remainingText = '';
                $position = strpos($blockFunctionName, ' ');
                
                if ($position !== false) {
                    $blockFunctionName = substr($segmentText, 0, $position);
                    $remainingText = substr($segmentText, $position+1);
                }

                if ($compileBlockFunction = $this->matchCompileBlockFunction($blockFunctionName)) {
                    $compileBlockFunction[0]($this, $remainingText);
                    return;
                }

                if ($processedBlockFunction = $this->doesRenderBlockExist($blockFunctionName)) {
                    $paramText = addslashes($remainingText);
                    $this->addCode("\$this->startRenderBlock('$blockFunctionName', '$paramText');");
                    return;
                }

                if (strpos($segmentText, '/') === 0) {
                    throw new JigException(
                        "Detected end of unknown block: ".$segmentText,
                        JigException::UNKNOWN_BLOCK
                    );
                }

                //$this->changeOutputMode(self::MODE_TEMPLATE);
                //It's a line of code that needs to be included.
                $this->changeOutputMode(self::MODE_CODE);
                $this->addLineInternal($segment->getString($this->parsedTemplate));
            }
        }
        catch (\Exception $e) {
            $message = "Could not parse template segment [{".$segmentText."}]: ".$e->getMessage();
            throw new \Jig\JigException($message, $e->getCode(), $e);
        }
    }

    /**
     * @param $text
     */
    public function addHTML($text)
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $text = str_replace("<?", "&lt;?", $text);
        $this->addLineInternal($text);
    }

    /**
     * @param $text
     */
    public function addCode($text)
    {
        $this->changeOutputMode(self::MODE_CODE);
        $this->addLineInternal($text);
        //$this->changeOutputMode(self::MODE_TEMPLATE);
    }

    /**
     * @param $segmentText
     * @throws \Exception
     */
    protected function processExtends($segmentText)
    {
        $pattern = '#file=[\'"]('.self::FILENAME_PATTERN.')[\'"]#u';

        $matchCount = preg_match($pattern, $segmentText, $matches);
        if ($matchCount == 0) {
            throw new JigException("Could not extract filename from [$segmentText] to extend.");
        }

        $this->parsedTemplate->setExtends($matches[1]);
    }

    /**
     * @param $segmentText
     * @throws JigException
     */
    protected function processInject($segmentText)
    {
        $namePattern = '#name=[\'"]('.self::FILENAME_PATTERN.')[\'"]#u';
        $valuePattern = '#type=[\'"](.*)[\'"]#u';
        $nameMatchCount = preg_match($namePattern, $segmentText, $nameMatches);
        $valueMatchCount = preg_match($valuePattern, $segmentText, $valueMatches);

        if ($nameMatchCount == 0) {
            throw new JigException("Failed to get name for injection");
        }
        if ($valueMatchCount == 0) {
            throw new JigException("Failed to get value for injection");
        }

        $name = $nameMatches[1];
        $type = $valueMatches[1];
        
        if (strlen($type) == 0) {
            throw new JigException("Value must not be zero length");
        }

        $this->parsedTemplate->addInjection($name, $type);
    }

    protected function processPlugin($segmentText)
    {
        $valuePattern = '#type=[\'"](.*)[\'"]#u';
        $valueMatchCount = preg_match($valuePattern, $segmentText, $valueMatches);

        if ($valueMatchCount == 0) {
            throw new JigException("Failed to get value for injection");
        }

        $classname = $valueMatches[1];
        //TODO - validate classname is valid
        
        if (strlen($classname) == 0) {
            throw new JigException("Type for filter must not be zero length");
        }

        $this->parsedTemplate->addPlugin($classname);
    }

    /**
     * @param $segmentText
     * @throws JigException
     */
    protected function processInclude($segmentText)
    {
        $pattern = '#file=[\'"]('.self::FILENAME_PATTERN.')[\'"]#u';

        $matchCount = preg_match($pattern, $segmentText, $matches);
        if ($matchCount != 0) {
            $this->setInclude($matches[1]);
            return;
        }

        if ($matchCount == 0) {
            throw new JigException("Could not extract filename from [$segmentText] to include.");
        }
    }

    /**
     * @param $segmentText
     * @throws \Exception
     */
    protected function processBlockStart($segmentText)
    {
        $pattern = '#name=[\'"]('.self::FILENAME_PATTERN.')[\'"]#u';
        $matchCount = preg_match($pattern, $segmentText, $matches);
        if ($matchCount == 0) {
            throw new JigException("Could not extract filename from [$segmentText] for blockStart.");
        }

        $blockName = $matches[1];

        if ($this->activeBlock != null) {
            throw new JigException("Trying to start block [$blockName] while still in a block. That's not possible.");
        }

        $this->activeBlock = array();
        $this->activeBlockName = $blockName;
    }

    /**
     *
     */
    protected function processBlockEnd()
    {
        $this->outputMode = self::MODE_CODE;

        if ($this->parsedTemplate->getExtends() == null) {
            $this->parsedTemplate->addTextLine(" \$this->".$this->activeBlockName."(); \n");
        }

        $this->parsedTemplate->addFunctionBlock($this->activeBlockName, $this->activeBlock);
        $this->activeBlock = null;
        $this->activeBlockName = null;
    }

    /**
     * @param $segmentText
     * @throws \RuntimeException
     * @throws \Jig\JigException
     */
    public function processForeachStart($segmentText)
    {
        //find the variable and replace it with new version
        $pattern = '/foreach\s+(\$?\w+[^\s\=]*)\s/u';

        $matchCount = preg_match($pattern, $segmentText, $matches, PREG_OFFSET_CAPTURE);

        if ($matchCount == 0) {
            throw new JigException("Could not extract variable to foreach over from [$segmentText].");
        }

        $foreachItem = $matches[1][0];
        $varPosition = $matches[1][1];
        $segmentText = str_replace('foreach', 'foreach (', $segmentText);

        if ($this->parsedTemplate->hasLocalVariable($foreachItem) == true) {
            $this->addLineInternal($segmentText.'){');
        }
        else {
            $segment = new PHPTemplateSegment($foreachItem);
            $replace = $segment->getString($this->parsedTemplate, ['nofilter', 'nophp', 'nooutput']);
            $segmentText = str_replace($foreachItem, $replace, $segmentText);
            $this->addCode($segmentText.'){ ');
        }

        $dependentVariablesPosition = $varPosition + strlen($foreachItem);

        $pattern = '/\s+(\$\w+)\s?/u';

        $matchCount = preg_match_all($pattern, $segmentText, $matches, PREG_PATTERN_ORDER, $dependentVariablesPosition);

        if ($matchCount == 0) {
            throw new \RuntimeException("Failed to parse foreach correctly.");
        }

        foreach ($matches[1] as $variableName) {
            $this->parsedTemplate->addLocalVariable($variableName);
        }
    }

    /**
     *
     */
    private function processForeachEnd()
    {
        $this->addCode(" } ");
    }

    /**
     * @param $segmentText
     */
    public function processPHPStart($segmentText)
    {
        $this->addCode(" \n");
        $this->setLiteralMode(self::LITERAL_PHP);
    }

    /**
     *
     */
    public function processPHPEnd()
    {
        $this->addCode("\n");
        $this->setLiteralMode(false);
    }
    
    
    /**
     * @param $textLine
     */
    private function addLineInternal($textLine)
    {
        if ($this->activeBlock !== null) {
            $this->activeBlock[] = $textLine;
        }
        else {
            $this->parsedTemplate->addTextLine($textLine);
        }
    }

    /**
     *
     */
    private function processLiteralStart()
    {
        $this->setLiteralMode(self::LITERAL_TEMPLATE);
    }

    /**
     *
     */
    private function processLiteralEnd()
    {
        $this->setLiteralMode(false);
    }

    /**
     * @param $templateFilename
     * @return string
     */
    public function setClassNameFromFilename($templateFilename)
    {
        return self::getClassNameFromFileName($templateFilename);
    }

    /**
     * Creates a 'named block' that is processed only when the template is compiled
     * from the template format to PHP, and binds a start and end callable to it.
     * @param $blockName
     * @param callable $startCallback
     * @param callable $endCallback
     */
    public function bindCompileBlock($blockName, callable $startCallback, callable $endCallback)
    {
        $this->compileBlockFunctions[$blockName] = array($startCallback, $endCallback);
    }

    /**
     *
     * @param $templateFilename
     * @return string
     */
    public function getClassNameFromFileName($templateFilename)
    {
        $templatePath = str_replace('/', '\\', $templateFilename);
        $templatePath = str_replace('-', '', $templatePath);
        return $templatePath;
    }

    /**
     * Generate the full class name for teh compiled version of a template.
     * @param $templateFilename
     * @return string
     */
    public function getNamespacedClassNameFromFileName($templateFilename)
    {
        $className = self::getClassNameFromFileName($templateFilename);

        return $this->jigConfig->getFullClassname($className);
    }
}
