{include file='pageStart'}


<div class="container">
    <div class="row">
        <div class="col-md-8">

<h4>Strings are automatically HTML filtered</h4>

<p>
    {$htmlString}
</p>


<h4>The HTML filtering can be disabled</h4>
<p>
    {$htmlString | nofilter}
</p>


<h4>Function call with output enabled</h4>
<p>
    {greet('john')}
</p>


<h3>Function call With output disabled</h4>
<p>
{greet('john') | nooutput}
</p>


<h4>Foreach variable </h4>
<p>
    {$colors = getColors()}

    {foreach $colors as $key => $color}
        <span style='color: {$color}'>{$color}</span>
    {/foreach}
</p>


<h4>Foreach from a function call</h4>
<p>
    {foreach getColors() as $key => $color}
        <span style='color: {$color}'>{$color}</span>
    {/foreach}
</p>


<h4>Same as previous, but as inline PHP.</h4>

<p>

<?php

    foreach ($this->call('getColors') as $key => $color) {
        echo "<span style='color: $color'>$color</span>";
    }

?>

</p>

        </div>
    </div>
</div>

{include file='pageEnd'}