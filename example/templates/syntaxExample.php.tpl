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

    {$colors = getColors()}

    <?php  var_dump($colors); ?>



    {foreach getColors() as $key => $color}
        hmm
    {/foreach}

</div>



{include file='pageEnd'}