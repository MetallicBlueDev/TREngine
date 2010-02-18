<div id="management_bar">
<div><a href="?mod=management"><img src="templates/default/management/bar_home.png" /></a></div>
<div><img src="templates/default/management/bar_update.png" /></div>
<div id="management_bar_setting_page"><select>
<?php 
foreach($pageList as $key => $page) {
	$selected = ($page == $pageSelected) ? "selected=\"selected\"" : "";
?>
<option value="<?php echo $page; ?>" <?php echo $selected; ?>><?php echo $pageName[$key]; ?></option>
<?php	
}
?>
</select></div>

<div id="management_bar_module_page"><select>
<?php 
foreach($pageList as $key => $page) {
?>
<option value="<?php echo $page; ?>"><?php echo $pageName[$key]; ?></option>
<?php	
}
?>
</select></div>
</div>