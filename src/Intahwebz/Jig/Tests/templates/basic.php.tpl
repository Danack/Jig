
{block name='mainContent'}


This is actually a template.

Hello there {$title} {$user} !!!!

<?php

for($x=0 ; $x<5 ; $x++){

	?>
	Does this work? <br/>


	<?php
}

?>

    {viewFunction(5)}

{/block}