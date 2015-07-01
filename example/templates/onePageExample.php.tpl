
<html>

<body>


<h2>Parameter injection</h2>

<div>

Injecting the appropriate object that implements 'ColorScheme' into the variable 'colorScheme'. <br/>
    
{inject name='colorScheme' type='JigDemo\Model\ColorScheme'}

Colors are: <br/>
<ul>
{foreach $colorScheme->getColors() as $color}
     <li>{$color}</li>
{/foreach}
</ul>


</div>


<h2>Literal tag</h2>

{literal}
    This is {literally} a literal string
{/literal}


<h2>Helper</h2>

{helper type='JigDemo\Helper\DemoHelper'}

{exampleFunction($colorScheme)}

<h2>Include</h2>

{include file="includedTemplate"}

<h2>Filters</h2>

<div>
    With default filtering: {getTextThatContainsHTML()}<br/>
    With default filtering disabled: {getTextThatContainsHTML() | nofilter}<br/>
</div>


<h2>Comments</h2>

<div>

Comments are not visible:
{* This is a comment and is not sent in the templates output *} <br/>
    
</div>


<h2>Trim</h2>

Trim removes white space:<br/>
<textarea rows="6" cols="70">{trim}
    This has space before and after, but it is trimmed.
        
        
{/trim}</textarea> <br/>



<h2>Raw PHP</h2>

<div>

<?php
    echo "This is some raw PHP: <br/>";
    echo "Memory limit is: ".ini_get('memory_limit');
?> 

</div>


</body>

</html>
