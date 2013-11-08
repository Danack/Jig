{include file='pageStart'}

{inject name='blogPost' value='Mapper\BlogPostMapper'}

{foreach $colors as $name => $value}

    <span style='color: {$value}'>{$name}</span>

{/foreach}



<h2>Blog posts</h2>

{foreach $blogPostList as $blogPost}

    <a href=''>{$blogPost->title}</a><br/>

{/foreach}



{include file='pageEnd'}