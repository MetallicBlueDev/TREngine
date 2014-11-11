<?php echo LibsBlock::getInstance()->getBlocksBySideName("moduletop"); ?>

<?php
$renderModule = LibsModule::getInstance()->getModule();

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

<?php echo LibsBlock::getInstance()->getBlocksBySideName("modulebottom"); ?>