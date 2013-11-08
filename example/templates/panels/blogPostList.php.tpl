

{inject name='blogPostList' value='Mapper\BlogPostList'}

<h2>Blog posts</h2>

{foreach $blogPostList as $blogPost}

    <a href='{$blogPost->url}'>{$blogPost->title}</a><br/>

{/foreach}

This is a <a href='/blogPost/10'>link to a non-existant blogPost</a>.