{include file='pageStart'}

<div>

This string is automatically filtered:<br/>

{$htmlString}

</div>

<div>

    With the filtering disabled:<br/>

{$htmlString | nofilter}

</div>


<div>
    Function call with output enabled:<br/>
    {greet('john')}
</div>

<div>
Function call With output disabled:<br/>
{greet('john') | nooutput}
</div>


<div>

    Foreach from a function call:

    {foreach getColors() as $key => $color}
        <span style='color: {$color}'>{$color}</span>
    {/foreach}
</div>

<div>

    Same as previous, but as inline PHP.<br/>

<?php

    foreach ($this->call('getColors') as $key => $color) {
        echo "<span style='color: $color'>$color</span>";
    }

?>

</div>




{include file='pageEnd'}