<?php 
if (Core_Main::isFullScreen()) {
?>
<div id="management_bar">
<div>
<a href="<?php echo Core_Html::getLink('?mod=management'); ?>"><img src="templates/default/management/bar_home.png" /></a>
</div>
<div>
<a href="<?php echo Core_Html::getLink('?mod=management&manage=update'); ?>"><img src="templates/default/management/bar_update.png" /></a>
</div>
<div id="management_bar_setting_page">
<select onchange="document.location='<?php echo Core_Html::getLink('?mod=management&manage='); ?>'+this.options[this.selectedIndex].value;">
<option value=""></option>
<?php
foreach($pageList as $key => $page) {
	$selected = ($page == $pageSelected) ? "selected=\"selected\"" : "";
?>
<option value="<?php echo $page; ?>" <?php echo $selected; ?>><?php echo $pageName[$key]; ?></option>
<?php	
}
?>
</select></div>

<div id="management_bar_module_page">
<select onchange="document.location='<?php echo Core_Html::getLink('?mod=management&manage='); ?>'+this.options[this.selectedIndex].value;">
<option value=""></option>
<?php 
foreach($moduleList as $key => $module) {
	$selected = ($module == $pageSelected) ? "selected=\"selected\"" : "";
?>
<option value="<?php echo $module; ?>" <?php echo $selected; ?>><?php echo $moduleName[$key]; ?></option>
<?php	
}
?>
</select></div>
</div>
<?php
}
?>