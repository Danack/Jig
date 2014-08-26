{include file='pageStart'}

<div class="container">
    <div class="row">
        <div class="col-md-8">


<h1>Binding data</h1>


<h2>Bind data through the view model</h2>


<p>

    The simplest way to get data into your template is to set it explicitly in the view model e.g.  <code>$viewmodel->setVariable('colors', $color)</code>.

    </p>

<p>
    This can then be used in a template as:
</p>

<p>
    <code>
    {literal}

    {foreach $colors as $name => $value}  <br/>
        &nbsp;&nbsp;&nbsp;&lt;span style='color: {$value}'&gt;{$name}&lt;/span&gt;<br/>
    {/foreach}<br/>


    {/literal}
    </code>
</p>

<p>
    {foreach $colors as $name => $value}
        <span style='color: {$value}'>{$name}</span>
    {/foreach}
</p>




        </div>
    </div>
</div>

{include file='pageEnd'}