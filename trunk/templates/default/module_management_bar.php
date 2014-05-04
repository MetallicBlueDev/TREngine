<?php
if (Core_Main::getInstance()->isDefaultLayout()) {
    ?>
    <div id="management_bar">
        <div>
            <?php echo Core_Html::getLink('?mod=management', '<img alt="" src="templates/default/management/bar_home.png" />'); ?>
        </div>
        <div>
            <?php echo Core_Html::getLink('?mod=management&manage=update', '<img alt=""  src="templates/default/management/bar_update.png" />'); ?>
        </div>
        <div id="management_bar_setting_page">
            <select onchange="document.location = '<?php echo Core_UrlRewriting::getLink('?mod=management&manage='); ?>' + this.options[this.selectedIndex].value;">
                <option value=""></option>
                <?php
                foreach ($pageList as $page) {
                    $selected = ($page['value'] == $pageSelected) ? "selected=\"selected\"" : "";
                    ?>
                    <option value="<?php echo $page['value']; ?>" <?php echo $selected; ?>><?php echo $page['name']; ?></option>
                    <?php
                }
                ?>
            </select>
        </div>

        <div id="management_bar_module_page">
            <select onchange="document.location = '<?php echo Core_UrlRewriting::getLink('?mod=management&manage='); ?>' + this.options[this.selectedIndex].value;">
                <option value=""></option>
                <?php
                foreach ($moduleList as $module) {
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
    include(TR_ENGINE_DIR . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "default" . DIRECTORY_SEPARATOR . "module_management_title.php");
}
?>