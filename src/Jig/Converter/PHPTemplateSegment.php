<?php


namespace Jig\Converter;


use Jig\JigException;
use PHPParser_Lexer;
use PHPParser_Parser;
use PHPParser_Error;

/**
 * Class PHPTemplateSegment
 */
class PHPTemplateSegment extends TemplateSegment
{
    public function __construct($text)
    {
        parent::__construct($text);
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

    // Replace variables
    // {$user} => $this->getVariable('user');

    // object variable
    // {$user->getName()} => $this->getVariable('user')->getName();

    // array variable
    // {$user['name']} => $this->getVariable('user')['name'];

    //Simple registered function
    //{someFunction()} => $this->someFunction();

    //Simple global function
    //{someFunction()} => someFunction();

    //function with variable
    //{someFunction($user)} => $this->someFunction($this->getVariable('user'));

    //Extends
    //{extends template='some/name'} => $template class extends "some/name"

    //{block name='someBlock'}		=> template function someBlock()
    //{/block}						=> end it

    //Foreach
    //{foreach $someArray as $key => $value}
    //=>
    //foreach ($this->getVariable('someArray') as $key => $value){}
    //{/foreach} =>  <?php } ? >

    // {$count = 1;}
    // {if ($count % 2) == 0}
    //{$count++}

    public function removeFilters()
    {
        $pattern = '/\|\s*([\w\s]+)/u';

        $filterMatch = preg_match($pattern, $this->text, $matches, PREG_OFFSET_CAPTURE);

        if (!$filterMatch) {
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
    public function getString(ParsedTemplate $parsedTemplate, $extraFilters = array())
    {
        //TODO this function is too big and needs to cache some
        //information to avoid repeating the same operations.
        $filters = $this->removeFilters();

        $filters = array_merge($filters, $extraFilters);

        $codePre = "<?php ";

        $code = $codePre;
        $code .= $this->text;
        $code .= " ?>";

        $parser = new PHPParser_Parser(new PHPParser_Lexer);

        try {
            $statements = $parser->parse($code);
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
        $segmentText = $printer->prettyPrint($statements);
        $segmentText = substr($segmentText, 0, strrpos($segmentText, ';'));
        
        $filters = array_merge($filters, $printer->getFilters());

        //$knownFilters = $parsedTemplate->getKnownFilters();

        foreach ($filters as $filterName) {
            if ($filterName == 'nofilter' ||
                $filterName == 'nooutput' ||
                $filterName == 'nophp') {
                continue;
            }
                        
            foreach ($parsedTemplate->getPlugins() as $pluginClasname) {
                $filterList = $pluginClasname::getFilterList();

                if (in_array($filterName, $filterList, true)) {
                    $filterParam = convertClassnameToParam($pluginClasname);
                    $segmentText = sprintf(
                        "\$this->%s->%s(%s)",
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
        
        if (in_array('nofilter', $filters) == false) {
            $segmentText = '\Jig\safeTextObject('.$segmentText.", ENT_QUOTES)";
        }

        if (in_array('nooutput', $filters) == false) {
            $segmentText = "echo ".$segmentText."";
        }

        if (in_array('nophp', $filters) == false) {
            //$segmentText = "<?php ".$segmentText."; ? >";
            $segmentText = $segmentText."; ";
        }

        return $segmentText;
    }

    //1. For all assignments, i.e. left of an equals sign - $parsedTemplate->addLocalVariable($assignmentMatch[1][0]);
    
    //2. Var all variable fetch, replace with
        //2a - if ($parsedTemplate->hasLocalVariable($variableName) == true)
            //just add it
        //2b - else
            //replace with $this->getVariable('".$variableName."')"
    
    //3. For all function calls - replace function with $this->call('functionName', $params).

}
