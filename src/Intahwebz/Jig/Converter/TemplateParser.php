<?php

namespace Intahwebz\Jig\Converter;

use Intahwebz\Jig\JigException;
use Intahwebz\Storage\S3Storage;

use Intahwebz\Utils\SafeAccess;

\Intahwebz\Functions::load();
\Intahwebz\MBExtra\Functions::load();


/**
 * Class TemplateParser
 *
 * Shitty class name detected. This is all of these at once:
 *
 * i) The parsed template.
 * ii) The template parser - which is a duplicate of PHPTemplateConverter.
 * iii) Parsed template output.
 *
 * //TODO refactor to something sensible.
 *
 * @package Intahwebz\PHPTemplate\Converter
 */

class TemplateParser {

    use SafeAccess;

    //TODO this is duplicated in ParsedTemplate
    const COMPILED_NAMESPACE = "Intahwebz\\JigTemplate";

	public static $filenamePattern = "[\.\w\\/]+";

    //TODO needs to be in a plugin
	const SYNTAX_START  =  "<!-- SyntaxHighlighter Start -->";

	//var $includedFilenames = array();
	private $activeBlock = null;
	private $activeBlockName = null;

	private $literalMode = false;


	public $proxied = false;

    /**
     * @var ParsedTemplate
     */
    public $parsedTemplate;

	private function __construct() {
        $this->parsedTemplate = new ParsedTemplate();
	}
	/**
	 * @param $fileLines
	 * @return TemplateParser
	 */
	static function createFromLines($fileLines) {
		$segments = array();
		foreach ($fileLines as $fileLine) {
			$nextSegments = self::processLine($fileLine);
			$segments = array_merge($segments, $nextSegments);
		}

		$templateParser = new TemplateParser($segments);

		foreach ($segments as $segment) {
			$templateParser->addSegment($segment);
		}

		return $templateParser;
	}

	/**
	 * @param $fileLine
	 * @return TemplateSegment[]
	 */
	static function processLine($fileLine) {
		$lineSegments = self::getLineSegments($fileLine);
		return $lineSegments;
	}

	/**
	 * @param $fileLine
	 * @return TemplateSegment[]
	 */
	static function getLineSegments($fileLine){
		$segments = array();
		$matches = array();

		$pattern = "/\{([^\s]+.*[^\s]+)\}/Uu";

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
		//$this->includedFilenames[] = $filename;
		$code = "\$this->view->includeFile('$filename')";
		$this->addCode($code);
	}

//    /**
//     * @return array
//     */
//    function getIncludedFilenames(){
//		return $this->includedFilenames;
//	}

    /**
     * @param TemplateSegment $segment
     * @throws \Exception
     */
    function addSegment(TemplateSegment $segment){
		$segmentText = $segment->text;

		if (strncmp($segmentText, '/literal', mb_strlen('/literal')) == 0){
			$this->processLiteralEnd();
			return;
		}
		else if (strncmp($segmentText, '/syntaxHighlighter', mb_strlen('/syntaxHighlighter')) == 0){
			$this->processSyntaxHighlighterEnd();
			return;
		}

		//Anything that escapes literal mode (i.e. /literal or /syntaxHighlighter) must be above this

		if ($this->literalMode == true) {
			$this->addLineInternal($segment->getRawString());
			return;
		}

		if ($segment instanceof TextTemplateSegment) {
			$this->addLineInternal($segment->getString($this));
		}
		else if ($segment instanceof PHPTemplateSegment) {
			$this->parsePHPTemplateSegment($segment);
		}
		else if ($segment instanceof CommentTemplateSegment) {
			$this->addCode($segment->getString($this));
		}
		else{
			throw new \Exception("Unknown Segment type ".get_class($segment));
		}
	}

    /**
     * @param TemplateSegment $segment
     */
    function parsePHPTemplateSegment(TemplateSegment $segment){
		$segmentText = $segment->text;

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
		else if (strncmp($segmentText, 'markdown', mb_strlen('markdown')) == 0){
			$this->processMarkdownStart();
		}
		else if (strncmp($segmentText, '/markdown', mb_strlen('/markdown')) == 0){
			$this->processMarkdownEnd();
		}
		else if (strncmp($segmentText, 'syntaxHighlighter', mb_strlen('syntaxHighlighter')) == 0){
			$this->processSyntaxHighlighterStart($segmentText);
		}
		else if (strncmp($segmentText, 'if ', mb_strlen('if ')) == 0){
			$origText = $segment->getString($this, ['nofilter', 'nophp', 'nooutput']);

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

			//TODO S3Storage is not part of this project.
			//It's a line of code that needs to be included.
			$this->addLineInternal($segment->getString($this));
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
    function processSyntaxHighlighterStart($segmentText) {
		$pattern = '#lang=[\'"]([\.\w]+)[\'"]#u';
		$matchCount = preg_match($pattern, $segmentText, $matches);
		if ($matchCount == 0) {
			throw new \Exception("Could not extract lang from [$segmentText] for syntaxHighlighter.");
		}

		$lang = $matches[1];

		$srcFile = false;

		$pattern = '#file=[\'"]([\.\w-]+)[\'"]#u';
		$matchCount = preg_match($pattern, $segmentText, $matches);
		if ($matchCount != 0) {
			$srcFile = $matches[1];
		}

		//TODO - this needs to be outside of Intahwebz?
		$this->addHTML(self::SYNTAX_START);

		if ($srcFile){
            //TODO - add error checking.
            //$rawLink = "/staticImage/original/".$srcFile;
            $rawLink = "/staticFile/".$srcFile;

            $this->addHTML("<pre class='brush: $lang; toolbar: true;' data-link='$rawLink'>");
            $this->setLiteralMode(true);

            $originalCacheFileName = S3Storage::getStaticLocalCacheFile($srcFile);
			$fileContents = htmlentities(file_get_contents($originalCacheFileName), ENT_QUOTES);

			$fileContents = str_replace("<?php ", "&lt;php", $fileContents);
			$fileContents = str_replace("?>", "?&gt;", $fileContents);

			$this->addHTML($fileContents);
		}
        else{
            $this->addHTML("<pre class='brush: $lang; toolbar: true;'>");
            $this->setLiteralMode(true);
        }
	}

    /**
     *
     */
    function processSyntaxHighlighterEnd() {
		$this->setLiteralMode(false);
		$this->addHTML("</pre>");
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
     *
     */
    function processMarkdownStart(){
		$this->addCode(" ob_start(); ");
	}

    /**
     *
     */
    function processMarkdownEnd(){
		$this->addCode(" \$contents = ob_get_contents();
		ob_end_clean();
		\$this->view->markdown(\$contents);
		");
	}


    /**
     * @param $segmentText
     * @throws \Exception
     */
    function processExtends($segmentText){
		$pattern = '#file=[\'"]('.self::$filenamePattern.')[\'"]#u';

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
		$pattern = '#file=[\'"]('.self::$filenamePattern.')[\'"]#u';

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
		$pattern = '#file=[\'"]('.self::$filenamePattern.')[\'"]#u';

		$matchCount = preg_match($pattern, $segmentText, $matches);
		if ($matchCount != 0) {
			$this->setInclude($matches[1]);
			return;
		}

		//dynamic include?
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
		$pattern = '#name=[\'"]('.self::$filenamePattern.')[\'"]#u';
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
			//Added in the correct spot, not in the active block
			//$this->textLines[] = " <?php \$this->".$this->activeBlockName."();  ? > ";
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
			//$this->textLines[] = $textLine;
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
	 * @param TemplateParser $templateParser
	 * @return string
	 * @throws \Exception
	 */
	function saveCompiledTemplate($compilePath) {
		$fullClassName = self::COMPILED_NAMESPACE."\\".$this->parsedTemplate->getClassName();

		$fullClassName = str_replace("/", "\\", $fullClassName);

		$namespace = getNamespace($fullClassName);
		$className = getClassName($fullClassName);

		if($this->proxied == true) {
			$parentFullClassName =	$fullClassName;
			$className = 'Proxied'.$className;
		}
		else if ($this->parsedTemplate->dynamicExtends != null) {
            //Dynamic extension does class extension at run time.
			$parentFullClassName = "\\Intahwebz\\Jig\\DynamicTemplateExtender";
		}
		else{
			$parentFullClassName = $this->parsedTemplate->getParentClass();
		}

		$parentFullClassName = str_replace("/", "\\", $parentFullClassName);

		$parentNamespace = getNamespace($parentFullClassName);
		$parentClassName = getClassName($parentFullClassName);

		$outputFilename = convertNamespaceClassToFilepath($namespace."\\".$className);
		$outputFilename = $compilePath.$outputFilename.".php";

		$startSection = <<< END
<?php

namespace $namespace;

use $parentFullClassName;

class $className extends $parentClassName {

END;

		ensureDirectoryExists($outputFilename);

		$outputFileHandle = @fopen($outputFilename, "w");

		if ($outputFileHandle == false) {
			throw new \Exception("Could not open file [$outputFilename] for writing template.");
		}

		fwrite($outputFileHandle, $startSection);

		if ($this->parsedTemplate->dynamicExtends != null) {
			$this->writeMappedSection($outputFileHandle);
		}

		if($this->proxied == true) {
			$this->writeProxySection($outputFileHandle);
		}
		else {
			$functionBlocks = $this->parsedTemplate->getFunctionBlocks();

			foreach ($functionBlocks as $name => $functionBlockSegments) {
				$this->writeFunction($outputFileHandle, $name, $functionBlockSegments);
			}
		}

		if ($this->parsedTemplate->getExtends() == null &&
            $this->parsedTemplate->dynamicExtends == null &&
            $this->proxied == false) {
			$remainingSegments = $this->parsedTemplate->getLines();
			$this->writeFunction($outputFileHandle, 'renderInternal', $remainingSegments);
		}

		$this->writeEndSection($outputFileHandle);

		fclose($outputFileHandle);

		if (class_exists($fullClassName) == false) {
			require($outputFilename);
		}
		else {
			//Warn - file was compiled when class already exists?
		}

		return $fullClassName;
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

		$dynamicExtends = $this->parsedTemplate->dynamicExtends;

		$output = <<< END
		public function __construct(\$view, \$mappedClassInfo = array()) {

			if (array_key_exists('$dynamicExtends', \$mappedClassInfo) == false) {
				throw new \Exception("Class '$dynamicExtends' not listed in mappedClassInfo, cannot proxy");
			}

			\$classInstanceName = \$view->getProxiedClass('$dynamicExtends');

			\$fullclassName = "\\\\Intahwebz\\\\PHPCompiledTemplate\\\\".\$classInstanceName;

            \$parentInstance = new \$fullclassName(\$this, \$view);
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

		function __construct(\$childInstance, \$view){
			\$this->childInstance = \$childInstance;
			\$this->view = \$view;
		}

END;

		fwrite($outputFileHandle, "\n");
		fwrite($outputFileHandle, $output);
		fwrite($outputFileHandle, "\n");

        $functionBlocks = $this->parsedTemplate->getFunctionBlocks();
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


    /**
     * @param $templateFilename
     */
    function setClassNameFromFilename($templateFilename){
		$className = self::getClassNameFromFileName($templateFilename);
		$this->parsedTemplate->setClassName($className);
	}

	/**
     *
     * //TODO - this is a global function?
	 * @param $templateFilename
	 * @return string
	 */
	static function getClassNameFromFileName($templateFilename){
		$templatePath = str_replace('/', '\\', $templateFilename);
		$templatePath = str_replace('-', '', $templatePath);
		return $templatePath;
	}

    /**
     * @param $templateFilename
     * @return string
     */
    static function getNamespacedClassNameFromFileName($templateFilename) {
		return self::COMPILED_NAMESPACE."\\".self::getClassNameFromFileName($templateFilename);
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