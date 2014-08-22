


{inject name='navLinks' value='Jig\Tests\SiteNavLinks'}


{foreach $navLinks as $navLink}
    navlink is {$navLink['url']} => {$navLink['description']}<br/>
{/foreach}

