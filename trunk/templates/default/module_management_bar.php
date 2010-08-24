<?php 
if (Core_Main::isFullScreen()) {
?>
	<div id="management_bar">
		<div>
			<a href="<?php echo Core_Html::getLink('?mod=management'); ?>"><img alt=""  src="templates/default/management/bar_home.png" /></a>
		</div>
		<div>
			<a href="<?php echo Core_Html::getLink('?mod=management&manage=update'); ?>"><img alt=""  src="templates/default/management/bar_update.png" /></a>
		</div>
		<div id="management_bar_setting_page">
			<select onchange="document.location='<?php echo Core_Html::getLink('?mod=management&manage='); ?>'+this.options[this.selectedIndex].value;">
				<option value=""></option>
				<?php
				foreach($pageList as $page) {
					$selected = ($page['value'] == $pageSelected) ? "selected=\"selected\"" : "";
				?>
					<option value="<?php echo $page['value']; ?>" <?php echo $selected; ?>><?php echo $page['name']; ?></option>
				<?php
				}
				?>
			</select>
		</div>

		<div id="management_bar_module_page">
			<select onchange="document.location='<?php echo Core_Html::getLink('?mod=management&manage='); ?>'+this.options[this.selectedIndex].value;">
			<option value=""></option>
			<?php
			foreach($moduleList as $module) {
				$selected = ($module['value'] == $pageSelected) ? "selected=\"selected\"" : "";
			?>
				<option value="<?php echo $module['value']; ?>" <?php echo $selected; ?>><?php echo $module['name']; ?></option>
			<?php
			}
			?>
			</select>
		</div>
	</div>
<?php
include(TR_ENGINE_DIR . "/templates/default/module_management_title.php");
}
?>