


{inject name='navLinks' type='Jig\PlaceHolder\SiteNavLinks'}


{foreach $navLinks as $navLink}
    navlink is {$navLink['url']} => {$navLink['description']}<br/>
{/foreach}

