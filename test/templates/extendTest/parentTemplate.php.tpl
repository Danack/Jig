

This is before the blocks.

{block name='firstBlock'}
    This is the first parent block.
{/block}


This is between the blocks

{block name='secondBlock'}
    This is the second parent block.
{/block}

{inject name='parent' type='JigTest\PlaceHolder\ParentDependency'}

{$parent->render()}

This is after the blocks.


