<?php echo Libs_Block::getInstance()->getBlocks("moduletop"); ?>

<?php 
$renderModule = Libs_Module::getInstance()->getModule();
	
if ($renderModule) {
?>
<div id="module_top"></div>
<div id="module_middle">
	<div style="padding: 10px 10px 10px 20px;">
		<?php echo $renderModule; ?>
	</div>
</div>
<div id="module_bottom"></div>
<?php
}
?>

<?php echo Libs_Block::getInstance()->getBlocks("modulebottom"); ?>