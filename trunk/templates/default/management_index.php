<?php include(TR_ENGINE_DIR . "/templates/default/management_bar.php"); ?>

<div id="management_index_page">
<?php

foreach($pageList as $page) {
	$pictureName = is_file(TR_ENGINE_DIR . "/templates/default/management/icon_" . $page['value'] . ".png") ? "icon_" . $page['value'] : "icon_no_picture";
?>
<div class="management_index_block">
<a href="<?php echo Core_Html::getLink('?mod=management&manage=' . $page['value']); ?>">
	<img alt=""  src="templates/default/management/<?php echo $pictureName; ?>.png" style="border: 0;" />
<div><b><?php echo $page['name']; ?></b></div></a>
</div>
<?php 
}

?>
</div>
<div class="cleaner"></div>