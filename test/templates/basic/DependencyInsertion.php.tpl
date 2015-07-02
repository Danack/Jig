


{inject name='navLinks' type='JigTest\PlaceHolder\SiteNavLinks'}


{foreach $navLinks as $navLink}
    navlink is {$navLink['url']} => {$navLink['description']}<br/>
{/foreach}

