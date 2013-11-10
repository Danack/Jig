{include file='pageStart'}

{inject name='navItems' value='Model\NavItems'}

<div class="container">

<h3>Examples</h3>

{foreach $navItems as $navItem}
    <a href="{$navItem->url}">{$navItem->description}</a> <br/>
{/foreach}

</div>

{include file='pageEnd'}

 