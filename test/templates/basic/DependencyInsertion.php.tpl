


{inject name='navLinks' value='Jig\PlaceHolder\SiteNavLinks'}


{foreach $navLinks as $navLink}
    navlink is {$navLink['url']} => {$navLink['description']}<br/>
{/foreach}

