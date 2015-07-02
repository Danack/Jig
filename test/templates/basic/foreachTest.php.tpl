
{block name='mainContent'}


{inject name='colors' type='JigTest\PlaceHolder\Values\Colors'}
{helper type='JigTest\Helper\ColorsHelper'}

Direct: {foreach $colors->getColors() as $color}{trim}
{$color}
{/trim}{/foreach}


From function: {foreach getColors() as $color}{trim}
{$color}
{/trim}{/foreach}

{/block}