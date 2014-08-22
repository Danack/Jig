{include file='pageStart'}

<div class="container">
    <div class="row">
        <div class="col-md-8">
            {block name='mainContent'}
            <p>
                This is the default mainContent block in the template '{getTemplate()}'. If the block is not declared in the extending template, it will be shown in the extending template.
            </p>
            {/block}
        </div>
    </div>
</div>

<footer>
    <div class="container">
      {block name='footer'}
          <p>
        This is the default footer content in the template '{getTemplate()}'. If the block is not declared in the extending template, it will be shown in the extending template.
          </p>
      {/block}
    </div>

</footer>

{include file='pageEnd'}

