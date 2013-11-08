{include file='pageStart'}


{foreach $colors as $name => $value}

    <span style='color: {$value}'>{$name}</span>

{/foreach}


{include file='panels/blogPostList'}

{include file='pageEnd'}