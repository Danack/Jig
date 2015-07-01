
{block name='mainContent'}
    
{inject name='userDetail' type='Jig\PlaceHolder\Values\UserDetails'}
    
This is a template.
{literal}
    Hello there {$userDetail->title} {$userDetail->name} !!!!
{/literal}
    

Hello there {$userDetail->title} {$userDetail->name} !!!!

<h3>Calling a function</h3>

Basic test passed.
    
{$foo = 5}
{if $foo}
    foo is truthy
{else}
    foo is falsy
{/if}
    
    
    
{/block}