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


<h3>Injecting data</h3>
<p>
    The much better way to way to bind data is to inject it as a variable from within the template. e.g.<br/>

    <code>
        {literal}
            {inject name='blogPostList' value='Mapper\BlogPostList'}
        {/literal}
    </code>
    <br/>

    Will create an instance of the class `Mapper\BlogPostList` as the variable `blogPostList` directly in the template without needing to set it up within the controller. This allows your controllers to be very light weight as your views are able to load the objects they require without any action by the controller.
</p>

{include file='panels/blogPostList'}

        </div>
    </div>
</div>

{include file='pageEnd'}