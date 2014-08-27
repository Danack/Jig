

Isset:
{if isset($unknownVariable)}
    Variable 'unknownVariable' was set.
{else}
    Variable 'unknownVariable' was not set
{/if}

 <br/>

Text is filtered by default: {$text} <br/>

Filtering can be disabled {$text | nofilter} <br/>

Bound variable '{$user}' <br/>

Bound function {boundFunction($user)}  <br/>

Bound callable {boundCallable()} <br/>

ViewModel method {testMethod()} <br/>

Injecting the appropriate object that implements 'ColorScheme' into the variable 'colorScheme'
{inject name='colorScheme' value='ColorScheme'}

{$colors = $colorScheme->getColors()}

Colors are:
{foreach $colors as $color}
     {$color}
{/foreach}

<br/>

{literal}
    This is {literally} a literal string
{/literal}


Comments are not visible:
{* This is a comment and is not sent in the templates output *} <br/>


Trim removes white space:<br/>
<textarea rows="4" cols="35">{trim}
    This has space before and after, but it is trimmed.
        
        
{/trim}</textarea> <br/>


Raw PHP
<?php
    echo "This is some raw PHP";
    
?> <br/>


<br/>
<br/>
<br/>
<br/>
<br/>


{ include } <br/>

{ extend } <br/>

