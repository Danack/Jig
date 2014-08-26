{include file='pageStart'}

<div class="container">
    <div class="row">
        <div class="col-md-8">


<h4>Literal block</h4>

<p>

{literal}
    This is a literal block. <br/> Everything between the start and end is shown {literally}.
{/literal}

</p>


<h4>Processed block</h4>
<p>

Warning block! 
    {warning}
    This text is wrapped by a processed block.
    {/warning}

</p>



<h4>Trim</h4>
<p>
{trim}

    This text

    is trimmed

{/trim}
</p>



        </div>
    </div>
</div>

{include file='pageEnd'}