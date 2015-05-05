<?php include TR_ENGINE_INDEXDIR . "/template/default/module_management_bar.php"; ?>

<div id="management_index_page">
    <?php
    foreach ($pageList as $page) {
        $pictureName = is_file(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "default" . DIRECTORY_SEPARATOR . "management" . DIRECTORY_SEPARATOR . "icon_" . $page['value'] . ".png") ? $page['value'] : "no_picture";
        ?>
        <div class="management_index_block">
            <?php echo TREngine\Engine\Core\CoreHtml::getLink('?mod=management&manage=' . $page['value'], '<img alt="" src="template/default/management/icon_' . $pictureName . '.png" style="border: 0;" /><div><b>' . $page['name'] . '</b></div>'); ?>
        </div>
        <?php
    }
    ?>
</div>
<div class="cleaner"></div>