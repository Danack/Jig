


{inject name='navLinks' value='Intahwebz\Jig\Tests\SiteNavLinks'}


{foreach $navLinks->links as $navLink}
    navlink is {$navLink['url']} => {$navLink['description']}<br/>
{/foreach}

