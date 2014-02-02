
{block name='mainContent'}



Direct: {foreach $colors as $color}{trim}
{$color}
{/trim}{/foreach}


Assigned: {foreach $colors as $color}{trim}
{$color}
{/trim}{/foreach}


Fromfunction {foreach getColors() as $color}{trim}
{$color}
{/trim}{/foreach}

{/block}