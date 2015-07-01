
{inject name='test' type='Jig\PlaceHolder\DynamicIncludeTest'}


{if $test->includeFile1()} 
    {include file='includeFile/include1'}
{else}
    {include file='includeFile/include2'}
{/if}





