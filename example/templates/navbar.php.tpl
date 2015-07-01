
{inject name='navItems' type='JigDemo\Model\NavItems'}


<div class="container">
    <nav class="navbar navbar-default" role="navigation">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="/">Jig examples</a>
        </div>
        <ul class="nav navbar-nav">
            {foreach $navItems as $navItem}
                <li>
                    <a href="{$navItem->url}">{$navItem->description}</a>
                </li>
            {/foreach}

        </ul>
    </nav>
</div>