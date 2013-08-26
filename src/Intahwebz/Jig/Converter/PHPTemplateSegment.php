<?php


namespace Intahwebz\Jig\Converter;

use Intahwebz\Jig\JigException;
use \PHPParser_Lexer;
use \PHPParser_Parser;
use \PHPParser_NodeTraverser;

/**
 * Sort to reverse order.
 * @param $a
 * @param $b
 * @return bool
 */
function sortReplacements($a, $b){
	return $a['position'] < $b['position'];
}

/**
 * Class PHPTemplateSegment
 * @package Intahwebz\Jig\Converter
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
		$knownFilters = array('nofilter', 'urlencode');

		$filterString = implode('|', $knownFilters);

		$pattern = '/\|\s*('.$filterString.')+/u';

//		if (strpos('nofilter', $this->text) !== false) {
//			echo "k ?";
//		}

		$filterCount = preg_match_all($pattern, $this->text, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE);

		$filters = array();

		$chomp = false;

		if ($filterCount != 0) {
			foreach($matches as $match) {
				$filters[] = $match[1][0];
				$length = strlen($match[0][0]);
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

	public function getEqualsPosition($code) {
		$position = 0;

		$tokens = token_get_all($code);

		foreach ($tokens as $token) {
			if (is_array($token) == true) {
				//It's not an equals token
				$position += strlen($token[1]);
			}
			else {
				if ($token == '=') {
					return $position;
				}
				$position += 1;
			}
		}

		return false;
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

//        echo "grr: ".$code."\n";
//        
//        static $count = 0;
//        
//        $count++;
//
//        if ($count >= 2) {
//            exit(0);
//        }
        
        $statements = $parser->parse($code);

        
        
        $printer = new TemplatePrinter($parsedTemplate);

        $segmentText = $printer->prettyPrint($statements);

        $segmentText = substr($segmentText, 0, strrpos($segmentText, ';'));

//        if ($equalsPosition != false) {
//            $filters[] = 'nooutput';
//        }

        if (in_array('nofilter', $filters) == false) {
            $segmentText =  "\safeTextObject(".$segmentText.", ENT_QUOTES)";
        }

        if (in_array('nooutput', $filters) == false) {
            $segmentText = "echo ".$segmentText."";
        }

        if (in_array('nophp', $filters) == false) {
            $segmentText = "<?php ".$segmentText." ; ?>";
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
    
    
	public function getStringOld(ParsedTemplate $parsedTemplate, $extraFilters = array()) {
		$filters = $this->removeFilters();

		$filters = array_merge($filters, $extraFilters);

		$codePre = "<?php ";

		$code = $codePre;
		$code .= $this->text;
		$code .= " ; ?>";

		$equalsPosition = false;

		//We can override $equalsPosition to make the whole line be a read, not assignment
		if ($equalsPosition === false) {
			$equalsPosition = $this->getEqualsPosition($code);
			$assignmentString = false;
		}

		if ($equalsPosition === false) {
			//$equalsPosition = 0;
		}
		else{
			$equalsPosition -= strlen($codePre);
			$assignmentString = substr($this->text, 0, $equalsPosition);
		}

		//TODO - find assignment thingies
		$variablePattern = '/(\$\w+)/u';

		if ($assignmentString !== false) {

			$assignmentMatchCount = preg_match_all($variablePattern, $assignmentString, $assignmentMatches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE);

			if ($assignmentMatchCount > 0) {
				foreach($assignmentMatches as $assignmentMatch){
					$parsedTemplate->addLocalVariable($assignmentMatch[1][0]);
				}
			}
		}

		$variableMatchCount = preg_match_all($variablePattern, $this->text, $variableMatches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE, $equalsPosition);

        //TODO - this doesn't work, we need to actually parse the code as PHP
        //Rather than use a regex.
		$functionPattern = '/(\w+\()[^\)]*\)/u';
        
        $debug = token_get_all('<?php '.$this->text.' ?>');
        
        
		$functionMatchCount = preg_match_all($functionPattern, $this->text, $functionMatches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE, $equalsPosition);

		$replaceInfoArray = array();

		if ($variableMatchCount > 0) {
			foreach($variableMatches as $variableMatch){
				$replaceInfo = array();
				$replaceInfo['match'] = $variableMatch[1][0];
				$replaceInfo['type'] = 'variable';
				$replaceInfo['position'] = $variableMatch[1][1];
				$replaceInfoArray[] = $replaceInfo;
			}
		}

		if ($functionMatchCount){
			foreach($functionMatches as $functionMatch){
				$replaceInfo = array();
				$replaceInfo['match'] = $functionMatch[1][0];
				$replaceInfo['type'] = 'function';
				$replaceInfo['position'] = $functionMatch[1][1];

				$letterBeforeFunctionPosition = $replaceInfo['position'] - 1;

				if ($letterBeforeFunctionPosition > 0){
					$char = substr($this->text, $letterBeforeFunctionPosition, 1);
					if ($char == '>') {
						continue;
					}
				}

				$replaceInfoArray[] = $replaceInfo;
			}
		}

        //This sorts replacements into the correct order for replacement, aka back to front.
		usort($replaceInfoArray, __NAMESPACE__.'\\sortReplacements');

//Correct order
//<?php echo \safeTextObject($this->call('showPagination', $this->getVariable('contentFilterData')->page, $this->getVariable('contentFilterData')->maxPages), ENT_QUOTES) ; ? >

//Incorrect order
//<?php echo \safeTextObject($this->call('showPagination', $this->getVariable('conten$this->getVariable('contentFilterData')e, $contentFilterData->maxPages), ENT_QUOTES) ; ? >


		$segmentText = $this->text;

		foreach ($replaceInfoArray as $replaceInfo) {
			if ($replaceInfo['type'] == 'function') {
				$functionName = $replaceInfo['match'];
				$start = substr($segmentText, 0, $replaceInfo['position']);
				$end = substr($segmentText, $replaceInfo['position'] + strlen($functionName));
				$end = trim($end);


				if (strpos($end, ')') === 0) {
					$replacement = '$this->call(\''.substr($functionName, 0, strlen($functionName) - 1)."'";
				}
				else{
					$replacement = '$this->call(\''.substr($functionName, 0, strlen($functionName) - 1)."', ";
				}
				$segmentText = $start.$replacement.$end;
			}
			else if ($replaceInfo['type'] == 'variable') {
				$start = substr($segmentText, 0, $replaceInfo['position']);
				$variableName = substr($replaceInfo['match'], 1);
				$end = substr($segmentText, $replaceInfo['position'] + strlen($variableName) + 1);

				if ($parsedTemplate->hasLocalVariable($variableName) == true) {
					//We have a local variable of the same name, just use that.
				}
				else{
					$segmentText = $start."\$this->getVariable('".$variableName."')".$end;
				}
			}
		}

		if ($equalsPosition != false) {
			$filters[] = 'nooutput';
		}

		if (in_array('nofilter', $filters) == false) {
			$segmentText =  "\safeTextObject(".$segmentText.", ENT_QUOTES)";
		}

		if (in_array('nooutput', $filters) == false) {
			$segmentText = "echo ".$segmentText."";
		}

		if (in_array('nophp', $filters) == false) {
			$segmentText = "<?php ".$segmentText." ; ?>";
		}

		return $segmentText;
	}
}

