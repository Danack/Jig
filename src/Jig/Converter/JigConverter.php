<?php


namespace Jig\Converter;

use Jig\JigException;
use Jig\JigConfig;


/**
 * Class JigConverter The actual class that converts templates into PHP. 
 */
class JigConverter {

    /**
     * @var \Jig\JigConfig
     */
    private $jigConfig;

    const FILENAME_PATTERN = '[\.\w\\/]+';

    //Suppress escaping HTML output
    const FILTER_NONE = 'nofilter';

    // Don't output any return from the function
    const FILTER_NO_OUTPUT = 'nooutput';

    // Suppress wrapping generated code with <?php ? > to allow modification
    // of the generated code.
    const FILTER_NO_PHP = 'nophp';
    
    private $literalMode = false;

    private $compileBlockFunctions = array();

    /**
     * These block function operate after the block has been converted.
     * @TODO - Steal the callable class from Auryn.
     * @var array
     */
    private $renderBlockFunctions = array();

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

    
    function __construct(JigConfig $jigConfig) {
        $this->bindRenderBlock('trim', [$this, 'processTrimEnd']);
        $this->jigConfig = $jigConfig;
    }

    /**
     * @param $blockName
     * @return null|callable
     */
    function matchCompileBlockFunction($segmentText) {
        foreach ($this->compileBlockFunctions as $blockName => $blockFunctions) {
            if (strncmp($segmentText, $blockName, mb_strlen($blockName)) == 0){
                return $blockFunctions;
            }
        }

        return null;
    }
    
    
    /**
     * @param $blockName
     * @return null|callable
     */
    function getProcessedBlockFunction($blockName) {
        if (array_key_exists($blockName, $this->renderBlockFunctions)) {
            return $this->renderBlockFunctions[$blockName];
        }
        return null;
    }

    
    /**
     * @param $fileLines
     * @throws \Exception
     * @return ParsedTemplate
     */
     function createFromLines($fileLines) {
        if ($this->parsedTemplate != null) {
            throw new \Exception("Trying to convert template while in the middle of converting another one.");
        }
        
        $this->parsedTemplate = new ParsedTemplate($this->jigConfig->compiledNamespace);

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
    function getLineSegments($fileLine) {
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

        //http://stackoverflow.com/questions/524548/regular-expression-to-detect-semi-colon-terminated-c-for-while-loops/524624#524624
//        You could write a little, very simple routine that does it, without using a regular expression:
//
//Set a position counter pos so that is points to just before the opening bracket after your for or while.
//Set an open brackets counter openBr to 0.
//Now keep incrementing pos, reading the characters at the respective positions, and increment openBr when you see an opening bracket, and decrement it when you see a closing bracket. That will increment it once at the beginning, for the first opening bracket in "for (", increment and decrement some more for some brackets in between, and set it back to 0 when your for bracket closes.
//        So, stop when openBr is 0 again.
//        

        $matchCount = preg_match_all($pattern, $fileLine, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE);

        if ($matchCount == 0) {
            $segments[] = new TextTemplateSegment($fileLine);
        }
        else{
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
    function setLiteralMode($literalMode){
        $this->literalMode = $literalMode;
    }


    /**
     * @param $filename
     */
    public function setInclude($filename) {
        $code = "echo \$this->jigRender->includeFile('$filename')";
        $this->addCode($code);
    }

    /**
     * @param TemplateSegment $segment
     * @throws \Exception
     */
    function addSegment(TemplateSegment $segment) {
        $segmentText = $segment->text;

        if (strncmp($segmentText, '/literal', mb_strlen('/literal')) == 0){
            $this->processLiteralEnd();
            return;
        }

        foreach ($this->compileBlockFunctions as $blockName => $blockFunctions) {
            if (strncmp($segmentText, '/'.$blockName, mb_strlen('/'.$blockName)) == 0){
                call_user_func($blockFunctions[1], $this, $segmentText);
                return;
            }
        }

        foreach ($this->renderBlockFunctions as $blockName => $blockFunctions) {
            if (strncmp($segmentText, '/'.$blockName, mb_strlen('/'.$blockName)) == 0){
                $this->addCode("\$this->jigRender->endRenderBlock('$blockName');");
                //$this->addCode("ob_end_clean();");
                return;
            }
        }

        //Anything that escapes literal mode (i.e. /literal or /syntaxHighlighter) must be above this
        if ($this->literalMode == true) {
            $this->addLineInternal($segment->getRawString());
            return;
        }

        //TODO this seems sub-optimal
        if ($segment instanceof TextTemplateSegment) {
            $this->addLineInternal($segment->getString($this->parsedTemplate));
        }
        else if ($segment instanceof PHPTemplateSegment) {
            $this->parseJigSegment($segment);
        }
        else if ($segment instanceof CommentTemplateSegment) {
            $this->addCode($segment->getString($this->parsedTemplate));
        }
        else{
            throw new \Jig\JigException("Unknown Segment type ".get_class($segment));
        }
    }

    /**
     * @param TemplateSegment $segment
     * @throws \Jig\JigException
     */
    function parseJigSegment(TemplateSegment $segment) {
        $segmentText = $segment->text;

        try{
            if (strncmp($segmentText, 'extends ', mb_strlen('extends ')) == 0){
                $this->processExtends($segmentText);
            }
            else if (strncmp($segmentText, 'dynamicExtends ', mb_strlen('dynamicExtends ')) == 0){
                $this->processDynamicExtends($segmentText);
            }
            else if (strncmp($segmentText, 'inject ', mb_strlen('inject ')) == 0){
                $this->processInject($segmentText);
            }
            else if (strncmp($segmentText, 'include ', mb_strlen('include ')) == 0){
                $this->processInclude($segmentText);
            }
            else if (strncmp($segmentText, 'block ', mb_strlen('block ')) == 0){
                $this->processBlockStart($segmentText);
            }
            else if (strncmp($segmentText, '/block', mb_strlen('/block')) == 0){
                $this->processBlockEnd();
            }
            else if (strncmp($segmentText, 'foreach', mb_strlen('foreach')) == 0){
                $this->processForeachStart($segmentText);
            }
            else if (strncmp($segmentText, '/foreach', mb_strlen('/foreach')) == 0){
                $this->processForeachEnd();
            }
            else if (strncmp($segmentText, 'literal', mb_strlen('literal')) == 0){
                $this->processLiteralStart();
            }
            //TODO - make less fragile
            else if (strncmp($segmentText, 'if isset', mb_strlen('isset')) == 0){
                $this->processIssetStart($segmentText);
            }
            else if (strncmp($segmentText, 'if ', mb_strlen('if ')) == 0){
                $segment->text = substr($segmentText, 3);
                $text = $segment->getString($this->parsedTemplate, ['nofilter', 'nophp', 'nooutput']);
                $this->addLineInternal('<?php if ('.$text.'){ ?>');
            }
            else if (strncmp($segmentText, '/if', mb_strlen('/if')) == 0){
                $this->addLineInternal('<?php } ?>');
            }
            else if (strncmp($segmentText, 'else', mb_strlen('else')) == 0){
                $this->addCode(" } else { ");
            }
            else{
                
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
                
                if ($processedBlockFunction = $this->getProcessedBlockFunction($blockFunctionName)) {
                    $startFunctionName = $processedBlockFunction[0];
                    if ($startFunctionName != null) {

                        $paramText = addslashes($remainingText);
                        
                        $this->addCode("\$this->jigRender->startRenderBlock('$blockFunctionName', '$paramText');");
                    }
                    $this->addCode("ob_start();");

                    return;
                }

                if (strpos($segmentText, '/') === 0) {
                    throw new JigException("Detected end of unknown block. Did you forget to bind ".$segmentText."?");
                }
                
                //It's a line of code that needs to be included.
                $this->addLineInternal($segment->getString($this->parsedTemplate));
            }
        }
        catch(\Exception $e) {
            $message = "Could not parse template segment [{".$segmentText."}]: ".$e->getMessage();
            throw new \Jig\JigException($message, $e->getCode(), $e);
        }
    }

    /**
     * @param $text
     */
    function addHTML($text) {
        $this->addLineInternal($text);
    }

    /**
     * @param $text
     */
    function addCode($text) {
        $this->addLineInternal("<?php ".$text." ?>");
    }

    /**
     * @param $segmentText
     * @throws \Exception
     */
    function processIssetStart($segmentText) {
        $pattern = '#isset\(\$([\w\[\]\']+)\)#u';

        $matchCount = preg_match($pattern, $segmentText, $match);

        if ($matchCount == 0) {
            throw new JigException("Could not extract variable from [$segmentText] to check isset.");
        }

        $code = 'if ($this->viewModel->isVariableSet(\''.addslashes($match[1]).'\') == true) {';
        $this->addCode($code);
    }

    /**
     * @param $segmentText
     * @throws \Exception
     */
    function processExtends($segmentText){
        $pattern = '#file=[\'"]('.self::FILENAME_PATTERN.')[\'"]#u';

        $matchCount = preg_match($pattern, $segmentText, $matches);
        if ($matchCount == 0) {
            throw new JigException("Could not extract filename from [$segmentText] to extend.");
        }

        $this->parsedTemplate->setExtends($matches[1]);
    }

    /**
     * @param $segmentText
     * @throws \Exception
     */
    function processDynamicExtends($segmentText) {
        $pattern = '#file=[\'"]('.self::FILENAME_PATTERN.')[\'"]#u';

        $matchCount = preg_match($pattern, $segmentText, $matches);
        if ($matchCount == 0) {
            throw new JigException("Could not extract filename from [$segmentText] to mapExtend.");
        }

        $this->parsedTemplate->setDynamicExtends($matches[1]);
    }


    function processInject($segmentText) {
        $namePattern = '#name=[\'"]('.self::FILENAME_PATTERN.')[\'"]#u';
        $valuePattern = '#value=[\'"](.*)[\'"]#u';
        $nameMatchCount = preg_match($namePattern, $segmentText, $nameMatches);
        $valueMatchCount = preg_match($valuePattern, $segmentText, $valueMatches);

        if ($nameMatchCount == 0) {
            throw new JigException("Failed to get name for injection");
        }
        if ($valueMatchCount == 0) {
            throw new JigException("Failed to get value for injection");
        }

        $name = $nameMatches[1];
        $value = $valueMatches[1];
        
        if(strlen($value) == 0) {
            throw new JigException("Value must not be zero length");
        }

        $this->parsedTemplate->addInjection($name, $value);
    }


    /**
     * @param $segmentText
     * @throws \Exception
     */
    function processInclude($segmentText) {
        $pattern = '#file=[\'"]('.self::FILENAME_PATTERN.')[\'"]#u';

        $matchCount = preg_match($pattern, $segmentText, $matches);
        if ($matchCount != 0) {
            $this->setInclude($matches[1]);
            return;
        }

        //dynamic include from variable
        $pattern = '#file=\$(\w+)#u';

        $matchCount = preg_match($pattern, $segmentText, $matches);
        if ($matchCount == 0) {
            throw new JigException("Could not extract filename from [$segmentText] to include.");
        }

        $code = "\$file = \$this->getVariable('".$matches[1]."');\n";
        $this->addCode($code);
        $code = "echo \$this->jigRender->includeFile(\$file)";
        $this->addCode($code);

    }

    /**
     * @param $segmentText
     * @throws \Exception
     */
    function processBlockStart($segmentText){
        $pattern = '#name=[\'"]('.self::FILENAME_PATTERN.')[\'"]#u';
        $matchCount = preg_match($pattern, $segmentText, $matches);
        if ($matchCount == 0) {
            throw new \Exception("Could not extract filename from [$segmentText] for blockStart.");
        }

        $blockName = $matches[1];

        if ($this->activeBlock != null) {
            throw new \Exception("Trying to start block [$blockName] while still in a block. That's not possible.");
        }

        $this->activeBlock = array();
        $this->activeBlockName = $blockName;
    }

    /**
     *
     */
    function processBlockEnd() {
        if ($this->parsedTemplate->getExtends() == null) {
            $this->parsedTemplate->addTextLine(" <?php \$this->".$this->activeBlockName."();  ?> ");
        }

        $this->parsedTemplate->addFunctionBlock($this->activeBlockName, $this->activeBlock);
        $this->activeBlock = null;
        $this->activeBlockName = null;
    }

    /**
     *
     */
    function processTrimEnd($content) {
        return trim($content);
    }


    /**
     * @param $segmentText
     * @throws \RuntimeException
     * @throws \Jig\JigException
     */
    function processForeachStart($segmentText){

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
            $this->addLineInternal( $segmentText.'){' );
        }
        else{
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
    function processForeachEnd() {
        $this->addCode(" } ");
    }

    /**
     * @param $textLine
     */
    private function addLineInternal($textLine) {
        if ($this->activeBlock !== null){
            $this->activeBlock[] = $textLine;
        }
        else {
            $this->parsedTemplate->addTextLine($textLine);
        }
    }

    /**
     *
     */
    function processLiteralStart(){
        $this->setLiteralMode(true);
    }

    /**
     *
     */
    function processLiteralEnd(){
        $this->setLiteralMode(false);
    }

    /**
     * @param $templateFilename
     * @return string
     */
    function setClassNameFromFilename($templateFilename){
        return self::getClassNameFromFileName($templateFilename);
    }

    /**
     * @param $blockName
     * @param callable $startCallback
     * @param callable $endCallback
     */
    function bindCompileBlock($blockName, Callable $startCallback, Callable $endCallback) {
        $this->compileBlockFunctions[$blockName] = array($startCallback, $endCallback);
    }

    function bindRenderBlock($blockName, $endFunctionCallable, $startFunctionCallable = null) {
        $this->renderBlockFunctions[$blockName] = array($startFunctionCallable, $endFunctionCallable);
    }

    /**
     *
     * 
     * @param $templateFilename
     * @return string
     */
     function getClassNameFromFileName($templateFilename){
        $templatePath = str_replace('/', '\\', $templateFilename);
        $templatePath = str_replace('-', '', $templatePath);
        return $templatePath;
    }

    /**
     * Generate the full class name for teh compiled version of a template.
     * @param $templateFilename
     * @param bool $proxied
     * @return string
     */
    function getNamespacedClassNameFromFileName($templateFilename, $proxied = false) {
        $className = self::getClassNameFromFileName($templateFilename);

        if ($proxied == true) {
            $lastSlashPosition = strrpos($className, '\\');
    
            if ($lastSlashPosition === false) {
                $className = 'Proxied'.$className;
            }
            else{
                $part1 = substr($className, 0, $lastSlashPosition + 1);
                $part2 = substr($className, $lastSlashPosition + 1);
                $className = $part1.'Proxied'.$part2;
            }
        }

        return $this->jigConfig->getFullClassname($className);
    }
}
