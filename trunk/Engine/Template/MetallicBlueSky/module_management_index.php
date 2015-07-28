<?php include TREngine\Engine\Lib\LibMakeStyle::getTemplateDir() . DIRECTORY_SEPARATOR . "module_management_bar.php"; ?>

<div id="management_index_page">
    <?php
    foreach ($pageList as $page) {
        $pictureName = is_file(TREngine\Engine\Lib\LibMakeStyle::getTemplateDir() . DIRECTORY_SEPARATOR . "management" . DIRECTORY_SEPARATOR . "icon_" . $page['value'] . ".png") ? $page['value'] : "no_picture";
        ?>
        <div class="management_index_block">
            <?php echo TREngine\Engine\Core\CoreHtml::getLink('?module=management&manage=' . $page['value'], '<img alt="" src="' . TREngine\Engine\Lib\LibMakeStyle::getTemplateDir() . '/management/icon_' . $pictureName . '.png" style="border: 0;" /><div><span class=\"text_bold\">' . $page['name'] . '</span></div>'); ?>
        </div>
        <?php
    }
    ?>
</div>
<div class="spacer"></div>