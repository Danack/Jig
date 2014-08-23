<?php


namespace Jig\Converter;

use \PHPParser_Lexer;
use \PHPParser_Parser;


/**
 * Class PHPTemplateSegment
 */
class PHPTemplateSegment extends TemplateSegment {

    /**
     * The pattern matcher strips off the enclosing tags - we re-add them here
     * for literal mode parsing.
     * @return string
     */
    public function getRawString(){
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

    public function removeFilters(){
        $knownFilters = array('nofilter', 'urlencode', 'nooutput', 'nophp');

        $filterString = implode('|', $knownFilters);

        $pattern = '/\|\s*('.$filterString.')+/u';

        $filterCount = preg_match_all($pattern, $this->text, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE);

        $filters = array();

        $chomp = false;

        if ($filterCount != 0) {
            foreach($matches as $match) {
                $filters[] = $match[1][0];
                //$length = strlen($match[0][0]);
                $position = $match[0][1];
                if ($chomp == false || $position < $chomp) {
                    $chomp = $position;
                }
            }
        }

        if ($chomp !== false) {
            $this->text = substr($this->text, 0, $chomp);
        }

        return $filters;
    }

    //TODO - this should be in converter.
    public function getString(ParsedTemplate $parsedTemplate, $extraFilters = array()) {
        $filters = $this->removeFilters();

        $filters = array_merge($filters, $extraFilters);

        $codePre = "<?php ";

        $code = $codePre;
        $code .= $this->text;
        $code .= " ?>";

        $parser = new PHPParser_Parser(new PHPParser_Lexer);

        $statements = $parser->parse($code);

        $printer = new TemplatePrinter($parsedTemplate);

        $segmentText = $printer->prettyPrint($statements);

        $segmentText = substr($segmentText, 0, strrpos($segmentText, ';'));

        if (in_array('nofilter', $filters) == false) {
            $segmentText =  '\safeTextObject('.$segmentText.", ENT_QUOTES)";
        }

        if (in_array('nooutput', $filters) == false) {
            $segmentText = "echo ".$segmentText."";
        }

        if (in_array('nophp', $filters) == false) {
            $segmentText = "<?php ".$segmentText." ; ?>";
        }

//        if (in_array('urlencode', $filters) == true) {
//            $segmentText = "\urlencode(".$segmentText."); ? >";
//        }

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

