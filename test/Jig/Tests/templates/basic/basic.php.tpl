
{block name='mainContent'}
    
This is a template.
{literal}
    Hello there {$title} {$user} !!!!
{/literal}
    

Hello there {$title} {$user} !!!!

<h3>Calling a function</h3>

{viewFunction(5)}
    
<h3>Raw PHP</h3>
    
<?php
for($x=0 ; $x<5 ; $x++){

	?>
	The value of $x is {$x} <br/>
    
	<?php
}
?>
    
    Basic test passed.
    
{$foo = 5}
{if $foo}
    foo is truthy
{else}
    foo is falsy
{/if}
    
    
    
{/block}