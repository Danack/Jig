
{block name='mainContent'}


{inject name='colors' value='Jig\PlaceHolder\Values\Colors'}
{helper type='Jig\Helper\ColorsHelper'}

Direct: {foreach $colors->getColors() as $color}{trim}
{$color}
{/trim}{/foreach}


From function: {foreach getColors() as $color}{trim}
{$color}
{/trim}{/foreach}

{/block}