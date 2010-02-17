<div id="management_index_page">

<?php

$count = 0;
foreach($pageList as $key => $page) {
	$count++;
	
	$pictureName = is_file("templates/default/management/" . $page . ".png") ? $page : "no_picture";
	
	echo "<div class=\"management_index_block\" style=\"float: left;\">"
	. "<a href=\"?mod=management&manage=" . $page . "\"><img src=\"templates/default/management/" . $pictureName . ".png\" style=\"border: 0;\" /></a>"
	. "<div><a href=\"?mod=management&manage=" . $page . "\">" . $pageName[$key] . "</a></div>"
	. "</div>";
}

?>

</div>
<div class="cleaner"></div>