
{block name='mainContent'}



direct{foreach $colors as $color}{trim}
{$color}
{/trim}{/foreach}


assigned{foreach $colors as $color}{trim}
{$color}
{/trim}{/foreach}


fromfunction{foreach getColors() as $color}{trim}
{$color}
{/trim}{/foreach}

{/block}