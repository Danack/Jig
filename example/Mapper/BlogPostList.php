<?php

namespace Mapper;

use Model\BlogPost;

use Intahwebz\Router;

class BlogPostList  implements \IteratorAggregate  {

    private $blogPosts = array();

    //In a real mapper you would be getting this stuff from a database and so would
    //have a dependency on a database connection object.
    function __construct(Router $router) {

        $this->blogPosts = [
            new BlogPost(
                1,
                $router->generateURLForRoute('blogPost', ['blogPostID' => 1]),
                "My first blog post",
                "Thundercats craft beer readymade Pitchfork, 90's sartorial drinking vinegar McSweeney's gastropub mustache flannel chia hashtag cray. Master cleanse direct trade roof party before they sold out Tonx, slow-carb gluten-free Helvetica authentic kitsch semiotics stumptown. Literally DIY disrupt, kale chips American Apparel +1 irony drinking vinegar cardigan meggings street art Neutra cornhole. "
            ),


            new BlogPost(
                2,
                $router->generateURLForRoute('blogPost', ['blogPostID' => 2]),
                'My second blog post',
                "Kale chips Tonx banjo tote bag deep v twee gastropub paleo, narwhal locavore XOXO sartorial Marfa. XOXO kale chips keytar, cornhole master cleanse tofu asymmetrical narwhal butcher skateboard. Odd Future semiotics messenger bag Terry Richardson 3 wolf moon Banksy. Direct trade fingerstache beard Odd Future gluten-free lo-fi. Photo booth hoodie whatever distillery, 90's pork belly raw denim Helvetica Brooklyn shabby chic cardigan " ),

            new BlogPost(
                3,
                $router->generateURLForRoute('blogPost', ['blogPostID' => 3]),
                'My second blog post',
                "Before they sold out sustainable Etsy fashion axe, master cleanse Schlitz direct trade pop-up. Flexitarian twee kogi, Helvetica chillwave next level vinyl cardigan 8-bit church-key Pinterest Truffaut freegan. Leggings Pinterest blog Etsy twee, Shoreditch 90's ennui vegan narwhal roof party butcher PBR&B. Narwhal hella VHS Shoreditch Etsy put a bird on it, ethnic hashtag Thundercats authentic sriracha. Kitsch swag sartorial, organic Etsy leggings Pitchfork tofu. " ),
        ];
    }

    function getBlogPost($blogPostID) {
        if ($blogPostID > 0 && $blogPostID < count($this->blogPosts)) {
            return $this->blogPosts[$blogPostID];
        }
    }

    public function getIterator() {
        return new \ArrayIterator($this->blogPosts);
    }
}

 