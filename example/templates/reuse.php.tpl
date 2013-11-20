
{include file='pageStart'}

<div class="container">

    <div class="row">
        <div class="col-md-8">

<h2>Re-using templates</h2>



<h4>Including</h4>

<p>
    The simplest way to re-use templates is to include them. e.g. each page on this set of examples has the start (and end) of the page in a separate template which is included by:</p>

<p><code>
        {literal}
        {include file='pageStart'}
    {/literal}
    </code> </p>

<p>

    This allows you to create re-usable chunks of HTML which are simple to reuse throughout your site.

</p>


<h4>Extending</h4>

<p>

    This allows you to inherit from templates and blah blah blah blah.

    <a href='/extend'>Extend example </a>


</p>


<h4>Dynamic extending</h4>

<p>
Dynamic extending is kind of dumb - but useful!

</p>


        </div>
    </div>
</div>



{include file='pageEnd'}
