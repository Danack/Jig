<?php


namespace Intahwebz\Jig\Converter;

use Intahwebz\Jig\JigException;
use Intahwebz\Utils\SafeAccess;

\Intahwebz\Functions::load();
\Intahwebz\MBExtra\Functions::load();



/**
 * Class JigConverter
 *
 * @package Intahwebz\Jig\Converter
 */
class JigConverter {
    
    use SafeAccess;

    //TODO this is duplicated in ParsedTemplate
    const COMPILED_NAMESPACE = "Intahwebz\\PHPCompiledTemplate";
    const FILENAME_PATTERN = "[\.\w\\/]+";
    
    const jigExtension = '';

    private $literalMode = false;
    public  $proxied = false;

    private $blockFunctions = array();

    private $processedBlockFunctions = array();

    /**
     * @var ParsedTemplate
     */
    public $parsedTemplate = null;

    private $activeBlock = null;
    private $activeBlockName = null;


    /**
     * @param $fileLines
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

                $endFunctionName = $blockFunctions[1];

                $this->addCode(" \$contents = ob_get_contents();
		ob_end_clean();
		\$this->view->".$endFunctionName."(\$contents);
		");
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
            throw new JigException("Unknown Segment type ".get_class($segment));
        }
    }

    /**
     * @param TemplateSegment $segment
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
                $origText = $segment->getString($this->parsedTemplate, ['nofilter', 'nophp', 'nooutput']);
    
                $ifPos = strpos($origText, 'if');
                $text = substr($origText, 0, $ifPos);
                $text .= "if (";
                $text .= substr($origText, $ifPos + 2);
    
                $this->addLineInternal('<?php '.$text.'){ ?>');
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
                            $this->addCode(" \$this->view->".$startFunctionName."(); ");
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
            throw new JigException($message, $e->getCode(), $e);
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
            throw new \Exception("Could not extract variable from [$segmentText] to check isset.");
        }

        $code = 'if ($this->view->isVariableSet(\''.addslashes($match[1]).'\') == true) {';
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
            $code = "\$file = \$this->view->getVariable('".$matches[1]."');\n";
            $this->addCode($code);
            //TODO add error handling when file is null
            $code = "\$this->view->includeFile(\$file)";
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
        if ($this->parsedTemplate->extends == null) {
            $this->parsedTemplate->addTextLine(" <?php \$this->".$this->activeBlockName."();  ?> ");
        }

        $this->parsedTemplate->addFunctionBlock($this->activeBlockName, $this->activeBlock);
        $this->activeBlock = null;
        $this->activeBlockName = null;
    }

    /**
     * @param $segmentText
     */
    function processTrimStart($segmentText){
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
     * @throws \Intahwebz\Jig\JigException
     */
    function processForeachStart($segmentText){
        //find the variable and replace it with new version
        $pattern = '/foreach\s+(\$\w+)\s/u';

        $matchCount = preg_match($pattern, $segmentText, $matches, PREG_OFFSET_CAPTURE);
        if ($matchCount == 0) {
            throw new JigException("Could not extract variable to foreach over from [$segmentText].");
        }

        $varName = $matches[1][0];
        $varPosition = $matches[1][1];
        $segmentText = str_replace('foreach', 'foreach (', $segmentText);

        if ($this->parsedTemplate->hasLocalVariable($varName) == true) {
            $this->addLineInternal( $segmentText.'){' );
        }
        else{
            $cVar = substr($varName, 1);
            $replace = "\$this->view->getVariable('$cVar')";
            $segmentText = str_replace($varName, $replace, $segmentText);
            $this->addCode($segmentText.'){ ');
        }

        $dependentVariablesPosition = $varPosition + strlen($varName);

        $pattern = '/\s+(\$\w+)\s?/u';

        $matchCount = preg_match_all($pattern, $segmentText, $matches, PREG_PATTERN_ORDER, $dependentVariablesPosition);

        foreach ($matches[1] as $variableName) {
            $this->parsedTemplate->addLocalVariable($variableName);
        }
    }

    /**
     *
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

    function bindProcessedBlock($blockName, $endFunctionName, $startFunctionName = null) {
        $this->processedBlockFunctions[$blockName] = array($startFunctionName, $endFunctionName);
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
     * @return string
     */
    function getNamespacedClassNameFromFileName($templateFilename) {
        return self::COMPILED_NAMESPACE."\\".self::getClassNameFromFileName($templateFilename).self::jigExtension;
    }

    /**
     * @return string
     */
    function getFullNameSpaceClassName() {
        $fullClassName = self::COMPILED_NAMESPACE."\\".$this->parsedTemplate->getClassName();
        return $fullClassName;
    }
}



?>