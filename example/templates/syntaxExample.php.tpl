{include file='pageStart'}

{helper type="JigDemo\Helper\DemoHelper"}


<div class="container">
    <div class="row">
        <div class="col-md-8">

<h4>Output is automatically filtered</h4>

<p>
    By default all output is filtered to escape HTML entities.<br/>
    Function output: {greet('<b>john</b>')} <br/>
</p>


<h4>Output filtering can be disabled</h4>
<p>
   
    Function output: {greet('<b>john</b>') | nofilter}<br/>
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


<h4>Inline PHP</h4>

<p>

    If you really, really need to, you can embed standard PHP into the template. This should only be used in extremis, for example to debug something that is otherwise impossible to debug. If you're using this on a regular basis you are definitely doing something horribly wrong.<br/>


    {$colors = getColors()}

<?php

    foreach ($colors as $key => $color) {
        echo "<span style='color: $color'>$color</span>";
    }

?>

</p>

<h4>There should be no gaps</h4>
<p>
    Hello { $user } does not get converted.
</p>
            
            




        </div>
    </div>
</div>

{include file='pageEnd'}