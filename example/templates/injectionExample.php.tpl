{include file='pageStart'}

<div class="container">

    <div class="row">
        <div class="col-md-8">


<h4>Binding variables</h4>


<p>


{foreach $colors as $name => $value}
    <span style='color: {$value}'>{$name}</span>
{/foreach}


</p>


<h2>Injecting dependencies</h2>

    {inject name='blogPost' value='Mapper\BlogPostMapper'}

<p>

{foreach $blogPostList as $blogPost}
    <a href='{$blogPost->url}'>{$blogPost->title}</a><br/>
{/foreach}

</p>

        </div>
    </div>
</div>

{include file='pageEnd'}