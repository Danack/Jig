

<?php echo "inline echo"; ?>
        
        
Another test.


< ? php  echo "Some text { / * This is inside quotes. * / } inside quotes";  ? >


{block name='inlinePHP'}

<?php 
    $x = 2;
    $x += 3;

    if ($x == 5) {
        echo "x value is $x";
    }
?>

{/block}