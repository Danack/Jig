<?php

namespace Jig\Converter;

use Jig\JigException;
use Mockery\CountValidator\Exception;
use PHPParser_Lexer;
use PHPParser_Parser;
use PHPParser_Error;

/**
 * Class PHPTemplateSegment
 */
class CodeTemplateSegment extends TemplateSegment
{
    private $statements;
    
    private $filters;
    
    const REMOVE_IF = 0x0001;
    const NO_FILTER = 0x0002;
    const NO_PHP    = 0x0004;
    const NO_OUTPUT = 0x0008;

    private $hasAssignment = false;
    
    private $isJigCommand = false;
    
    public function __construct($text)
    {
        $this->text = $text;
        $this->filters = $this->removeFilters();
    }

    public function setIsJigCommand($isJigCommand)
    {
        $this->isJigCommand = $isJigCommand;
    }
    
    /**
     * @return bool
     */
    public function hasAssignment()
    {
        return $this->hasAssignment;
    }
    
    public function isOutputLine()
    {
        if ($this->hasAssignment === true) {
            return false;
        }

        if ($this->isJigCommand === true) {
            return false;
        }

        return true;
    }

    public function setHasAssignment($hasAssignment)
    {
        $this->hasAssignment = $hasAssignment;
    }
    
    /**
     * The pattern matcher strips off the enclosing tags - we re-add them here
     * for literal mode parsing.
     * @return string
     */
    public function getRawString()
    {
        return '{'.$this->text.'}';
    }

    private function removeFilters()
    {
        $pattern = '/\|\s*([\w\s]+)/u';
        $filterMatch = preg_match($pattern, $this->text, $matches, PREG_OFFSET_CAPTURE);
        if ($filterMatch === 0 || $filterMatch === false) {
            return [];
        }

        $filterStartPosition = $matches[0][1];
        $filterText = $matches[1][0];
        $filterText = str_replace("\t", ' ', $filterText);
        $filters = explode(' ', $filterText);

        $this->text = substr($this->text, 0, $filterStartPosition);

        return $filters;
    }
    
    /**
     * @param ParsedTemplate $parsedTemplate
     * @param array $extraFilters
     * @return string
     * @throws JigException
     */
    public function getCodeString(
        ParsedTemplate $parsedTemplate,
        $flags = 0
    ) {
        $filters = $this->filters;

        if (($flags & self::NO_FILTER) !== 0) {
            $filters[] = JigConverter::FILTER_NONE;
        }

        if (count($filters) === 0) {
            $filters[] = JigConverter::FILTER_HTML;
        }

        $code = "<?php ";
        
        if (($flags & self::REMOVE_IF) !== 0) {
            $code .= substr($this->text, 3);
        }
        else {
            $code .= $this->text;
        }
        $code .= " ?>";

        $parser = new PHPParser_Parser(new PHPParser_Lexer);

        try {
            $this->statements = $parser->parse($code);
        }
        catch (PHPParser_Error $parserError) {
            $message = sprintf(
                "Failed to parse code: [%s] error is %s",
                $parserError->getRawLine(),
                $parserError->getRawMessage()
            );

            throw new JigException(
                $message,
                0,
                $parserError
            );
        }

        $printer = new TemplatePrinter($parsedTemplate);
        $segmentText = $printer->prettyPrint($this->statements);
        $segmentText = substr($segmentText, 0, strrpos($segmentText, ';'));
        
        $filters = array_merge($filters, $printer->getFilters());
        
        if ($printer->hasAssignment() === true) {
            $this->hasAssignment = true;
            return $segmentText."; ";
        }
        
        $this->hasAssignment = false;
        
        foreach ($filters as $filterName) {
            switch ($filterName) {
                case (JigConverter::FILTER_NONE): {
                    break 2;
                }
                case (JigConverter::FILTER_HTML): {
                    $segmentText = '$this->escaper->escapeHTML('.$segmentText.')';
                    break 2;
                }
                
                case (JigConverter::FILTER_HTML_ATTR): {
                    $segmentText = '$this->escaper->escapeHTMLAttribute('.$segmentText.')';
                    break 2;
                }
                
                case (JigConverter::FILTER_JS): {
                    $segmentText = '$this->escaper->escapeJavascript('.$segmentText.')';
                    break 2;
                }
                
                case (JigConverter::FILTER_CSS): {
                    $segmentText = '$this->escaper->escapeCSS('.$segmentText.')';
                    break 2;
                }
                
                case (JigConverter::FILTER_URL): {
                    $segmentText = '$this->escaper->escapeURLComponent('.$segmentText.')';
                    break 2;
                }
            }

            foreach ($parsedTemplate->getPlugins() as $pluginClasname) {
                $filterList = $pluginClasname::getFilterList();

                if (in_array($filterName, $filterList, true) === true) {
                    $filterParam = JigConverter::convertClassnameToParam($pluginClasname);
                    $segmentText = sprintf(
                        "\$this->%s->callFilter('%s', %s)",
                        $filterParam,
                        $filterName,
                        $segmentText
                    );

                    continue 2;
                }
            }

            throw new JigException(
                "Template is trying to use filter '$filterName', which is not known.",
                JigException::UNKNOWN_FILTER
            );
        }

        if (($flags & self::NO_OUTPUT) === 0) {
            $segmentText = "echo ".$segmentText."";
        }

        if (($flags & self::NO_PHP) === 0) {
            $segmentText = $segmentText."; ";
        }

        return $segmentText;
    }
}
