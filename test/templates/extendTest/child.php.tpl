
{extends file="extendTest/parentTemplate"}

{inject name='child' type='JigTest\PlaceHolder\ChildDependency'}

{block name='secondBlock'}
    
    {$child->render()}
    
    
    This is the second child block.
{/block}