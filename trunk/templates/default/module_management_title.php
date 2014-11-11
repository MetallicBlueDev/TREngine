<div id="management_setting">
    <div id="management_setting_title">
        <div id="management_setting_title_icon">
            <?php
            $pictureName = is_file(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "default" . DIRECTORY_SEPARATOR . "management" . DIRECTORY_SEPARATOR . "icon_" . $pageSelected . ".png") ? $pageSelected : "no_picture";
            ?>
            <img src="templates/default/management/icon_<?php echo $pictureName; ?>.png" />
        </div>
        <div id="management_setting_title_text"><?php echo $currentPageName; ?></div>
    </div>

    <div id="management_setting_toolbar">
        <?php
        if (!empty($toolbar)) {
            foreach ($toolbar as $button) {
                ?>
                <?php
                echo Core_Html::getLink("?mod=management&manage=" . $pageSelected . "&" . $button['link'], '
                    <div class="management_setting_toolbar_button">
                        <div class="management_setting_toolbar_button_' . $button['name'] . '">'
                . $button['description'] . '
                        </div>
                    </div>');
                ?>
                <?php
            }
        }
        ?>
    </div>

</div>
<div class="cleaner"></div>