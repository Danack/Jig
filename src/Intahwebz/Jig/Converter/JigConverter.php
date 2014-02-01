<?php


namespace Intahwebz\Jig\Converter;

use Intahwebz\Jig\JigException;
use Intahwebz\SafeAccess;

\Intahwebz\Functions::load();
\Intahwebz\MBExtra\Functions::load();


class JigConverter {
    
    use SafeAccess;

    //TODO this is duplicated in ParsedTemplate
    const COMPILED_NAMESPACE = "Intahwebz\\PHPCompiledTemplate";
    const FILENAME_PATTERN = "[\.\w\\/]+";


    //Suppress escaping HTML output
    const FILTER_NONE = 'nofilter';

    // Don't output any return from the function
    const FILTER_NO_OUTPUT = 'nooutput';

    // Suppress wrapping generated code with <?php ? > to allow modification
    // of the generated code.
    const FILTER_NO_PHP = 'nophp';


    private $literalMode = false;
    public  $proxied = false;

    private $blockFunctions = array();

    /**
     * TODO - Steal the callable class from Auryn.
     * @var array
     */
    private $processedBlockFunctions = array();

    /**
     * @var ParsedTemplate
     */
    public $parsedTemplate = null;

    private $activeBlock = null;
    private $activeBlockName = null;


    function getProcessedBlockFunction($blockName) {
        if (array_key_exists($blockName, $this->processedBlockFunctions)) {
            return $this->processedBlockFunctions[$blockName];
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
        
        $this->parsedTemplate = new ParsedTemplate(self::COMPILED_NAMESPACE);

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
    function getLineSegments($fileLine){
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
        $pattern = "/\{([^\s]+.*[^\s]+)\}/Uu";
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
    public function setInclude($filename){
        $code = "\$this->jigRender->includeFile('$filename')";
        $this->addCode($code);
    }

    /**
     * @param TemplateSegment $segment
     * @throws \Exception
     */
    function  addSegment(TemplateSegment $segment){
        $segmentText = $segment->text;

        if (strncmp($segmentText, '/literal', mb_strlen('/literal')) == 0){
            $this->processLiteralEnd();
            return;
        }

        foreach ($this->blockFunctions as $blockName => $blockFunctions) {
            if (strncmp($segmentText, '/'.$blockName, mb_strlen('/'.$blockName)) == 0){
                call_user_func($blockFunctions[1], $this, $segmentText);
                return;
            }
        }

        foreach ($this->processedBlockFunctions as $blockName => $blockFunctions) {

            if (strncmp($segmentText, '/'.$blockName, mb_strlen('/'.$blockName)) == 0){
                $this->addCode("\$this->jigRender->endProcessedBlock('$blockName');");
                return;
            }
        }

        //Anything that escapes literal mode (i.e. /literal or /syntaxHighlighter) must be above this
        if ($this->literalMode == true) {
            $this->addLineInternal($segment->getRawString());
            return;
        }

        //TODO this is bad code
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
            throw new \Intahwebz\Jig\JigException("Unknown Segment type ".get_class($segment));
        }
    }


    
    /**
     * @param TemplateSegment $segment
     * @throws \Intahwebz\Jig\JigException
     */
    function parseJigSegment(TemplateSegment $segment){
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
            else if (strncmp($segmentText, 'spoiler', mb_strlen('spoiler ')) == 0){
                $this->processSpoilerBlockStart();
            }
            else if (strncmp($segmentText, '/spoiler', mb_strlen('/spoiler')) == 0){
                $this->processSpoilerBlockEnd();
            }
            else if (strncmp($segmentText, 'trim ', mb_strlen('trim')) == 0){
                $this->processTrimStart($segmentText);
            }
            else if (strncmp($segmentText, '/trim', mb_strlen('/trim')) == 0){
                $this->processTrimEnd();
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
            else if (strncmp($segmentText, 'isset', mb_strlen('isset')) == 0){
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
                foreach ($this->blockFunctions as $blockName => $blockFunctions) {
                    if (strncmp($segmentText, $blockName, mb_strlen($blockName)) == 0){
                        $blockFunctions[0]($this, $segmentText);
                        return;
                    }
                }

                foreach ($this->processedBlockFunctions as $blockName => $blockFunctions) {
                    if (strncmp($segmentText, $blockName, mb_strlen($blockName)) == 0){

                        $startFunctionName = $blockFunctions[0];

                        if ($startFunctionName != null) { 
                            $this->addCode("\$this->jigRender->startProcessedBlock('$segmentText');");
                        }

                        $this->addCode(" ob_start(); ");
                        return;
                    }
                }
    
                //It's a line of code that needs to be included.
                $this->addLineInternal($segment->getString($this->parsedTemplate));
            }
        }
        catch(\Exception $e) {
            $message = "Could not parse template segment [{".$segmentText."}]: ".$e->getMessage();
            throw new \Intahwebz\Jig\JigException($message, $e->getCode(), $e);
        }
    }

    /**
     * @param $text
     */
    function addHTML($text){
        $this->addLineInternal($text);
    }

    /**
     * @param $text
     */
    function addCode($text){
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
            throw new \Exception("Could not extract filename from [$segmentText] to extend.");
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
            throw new \Exception("Could not extract filename from [$segmentText] to mapExtend.");
        }

        $this->parsedTemplate->setDynamicExtends($matches[1]);
    }


    function processInject($segmentText) {

        $namePattern = '#name=[\'"]('.self::FILENAME_PATTERN.')[\'"]#u';
        $valuePattern = '#value=[\'"](.*)[\'"]#u';
        $nameMatchCount = preg_match($namePattern, $segmentText, $nameMatches);
        $valueMatchCount = preg_match($valuePattern, $segmentText, $valueMatches);

        if ($nameMatchCount == 0) {
            throw new \Exception("Failed to get name for injection");
        }

        if ($valueMatchCount == 0) {
            throw new \Exception("Failed to get value for injection");
        }

        $name = $nameMatches[1];
        $value = $valueMatches[1];

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

        //dynamic include from variable?
        $pattern = '#file=\$(\w+)#u';

        $matchCount = preg_match($pattern, $segmentText, $matches);
        if ($matchCount != 0) {
            $code = "\$file = \$this->getVariable('".$matches[1]."');\n";
            $this->addCode($code);
            //TODO add error handling when file is null
            $code = "\$this->jigRender->includeFile(\$file)";
            $this->addCode($code);
            return;
        }

        throw new \Exception("Could not extract filename from [$segmentText] to include.");
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
     * @param $segmentText
     */
    function processTrimStart(/** @noinspection PhpUnusedParameterInspection */
        $segmentText){
        $this->addCode("ob_start();");
    }

    /**
     *
     */
    function processTrimEnd() {
        $this->addCode('$output = ob_get_contents();');
        $this->addCode('ob_end_clean();');
        $this->addCode('echo trim($output);');
    }


    /**
     * @param $segmentText
     * @throws \RuntimeException
     * @throws \Intahwebz\Jig\JigException
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
     * @TODO - remove this and allow block function plugins.
     */
    function processSpoilerBlockStart(){
        $spoiler = "<div>";
        $spoiler .= "<span class='clickyButton' onclick='showHide(this, \"spoilerHidden\");'>Spoiler</span>";
        $spoiler .= "<div class='spoilerBlock' style=''>";
        $spoiler .= "<div class='spoilerHidden' style='display: none;'>";
        $this->addLineInternal($spoiler);
    }

    /**
     *
     */
    function processSpoilerBlockEnd(){
        $this->addLineInternal("<div style='clear: both;'></div>");
        $this->addLineInternal("</div>");
        $this->addLineInternal("</div></div>");
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
    function bindBlock($blockName, Callable $startCallback, Callable $endCallback) {
        $this->blockFunctions[$blockName] = array($startCallback, $endCallback);
    }

    function bindProcessedBlock($blockName, $endFunctionCallable, $startFunctionCallable = null) {
        $this->processedBlockFunctions[$blockName] = array($startFunctionCallable, $endFunctionCallable);
    }

    /**
     *
     * //TODO - this is a global function?
     * @param $templateFilename
     * @return string
     */
     function getClassNameFromFileName($templateFilename){
        $templatePath = str_replace('/', '\\', $templateFilename);
        $templatePath = str_replace('-', '', $templatePath);
        return $templatePath;
    }

    /**
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

        return self::COMPILED_NAMESPACE."\\".$className;
    }

    /**
     * @return string
     */
    function getFullNameSpaceClassName() {
        $fullClassName = self::COMPILED_NAMESPACE."\\".$this->parsedTemplate->getClassName();
        return $fullClassName;
    }
}
