{include file='pageStart'}


<div class="container">
    <h4>Create a blog post</h4>

    <div style="max-width: 640px">
        {$form->render() | nofilter}
    </div>
</div>

{include file='pageEnd'}