

{inject name='blogPostList' value='Mapper\BlogPostList'}

<h4>Blog posts</h4>

{foreach $blogPostList as $blogPost}

    <a href='{$blogPost->url}'>{$blogPost->title}</a><br/>

{/foreach}

This is a <a href='/blogPost/10'>link to a non-existant blogPost</a>.