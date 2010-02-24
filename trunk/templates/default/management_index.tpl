<?php include(TR_ENGINE_DIR . "/templates/default/management_bar.tpl"); ?>

<div id="management_index_page">
<?php

foreach($pageList as $key => $page) {
	$pictureName = is_file(TR_ENGINE_DIR . "/templates/default/management/icon_" . $page . ".png") ? "icon_" . $page : "icon_no_picture";
?>
<div class="management_index_block">
<a href="?mod=management&manage=<?php echo $page; ?>"><img src="templates/default/management/<?php echo $pictureName; ?>.png" style="border: 0;" /></a>
<div><a href="?mod=management&manage=<?php echo $page; ?>"><b><?php echo $pageName[$key]; ?></b></a></div>
</div>
<?php 
}

?>
</div>
<div class="cleaner"></div>