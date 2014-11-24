<?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBySideName("moduletop"); ?>

<?php
$renderModule = TREngine\Engine\Lib\LibModule::getInstance()->getModule();

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

<?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBySideName("modulebottom"); ?>