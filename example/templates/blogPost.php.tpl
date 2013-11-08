
{include file='pageStart'}

The first blogPost is inserted by the controller, which works but takes lots of effort to setup.


<h2>{$blogPost->title}</h2>

<div>
    {$blogPost->text}
</div>



{inject name='blogPostInjected' value='Mapper\BlogPost'}

<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>

The second instance is directly inserted into the template via a 'Mapper\BlogPost' object.

<h2>{$blogPostInjected->title}</h2>

<div>
{$blogPostInjected->text}
</div>



{include file='panels/blogPostList'}



{include file='pageEnd'}
