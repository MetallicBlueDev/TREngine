<?php echo Libs_Block::getInstance()->getBlocksBySideName("moduletop"); ?>

<?php
$renderModule = Libs_Module::getInstance()->getModule();

if (!empty($renderModule)) {
    ?>
    <div id="module_top"></div>
    <div id="module_middle">
        <div id="module_middle_content">
            <?php echo $renderModule; ?>
        </div>
    </div>
    <div id="module_bottom"></div>
    <?php
}
?>

<?php echo Libs_Block::getInstance()->getBlocksBySideName("modulebottom"); ?>