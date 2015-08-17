
{php}echo "inline echo";{/php}
        
Another test.

{php}  echo "Some text { / * This is inside quotes. * / } inside quotes";  {/php}


{block name='testingBlock'}
{php}
    $x = 2;
    $x += 3;

    if ($x == 5) {
        echo "x value is $x";
    }
{/php}

{/block}