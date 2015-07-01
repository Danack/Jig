
{include file='pageStart'}

<div class="container">

    <div class="row">
        <div class="col-md-8">

<h4>Set variable in viewmodel</h4>

<p>
The first blogPost is loaded in the controller, and then set in the view model by calling <code>$viewmodel->setVariable('blogPost', $blogPost)</code>, which works but takes lots of effort to setup.


</p>


<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">{$blogPost->title}</h3>
    </div>
    <div class="panel-body">
        {$blogPost->text}
    </div>
</div>


<h4>Injecting the object</h4>



<p>
The second instance is directly inserted into the template via a 'Mapper\BlogPost' object.
</p>

{inject name='blogPostInjected' type='Mapper\BlogPost'}

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">{$blogPostInjected->title}</h3>
    </div>
    <div class="panel-body">
        {$blogPostInjected->text}
    </div>
</div>


<hr/>

{include file='panels/blogPostList'}


        </div>
    </div>
</div>



{include file='pageEnd'}
