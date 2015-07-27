<!DOCTYPE html>
<html lang="fr">
    <head>
        <title><?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaTitle(); ?></title>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaHeaders(); ?>
        <link rel="stylesheet" href="<?php echo TREngine\Engine\Lib\LibMakeStyle::getTemplateDir(); ?>/index_engine.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo TREngine\Engine\Lib\LibMakeStyle::getTemplateDir(); ?>/index_template.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo TREngine\Engine\Lib\LibMakeStyle::getTemplateDir(); ?>/close.css" type="text/css" />
    </head>
    <body>
        <div style="display: none;">
            <?php echo TREngine\Engine\Core\CoreMain::getInstance()->getDefaultSiteName(); ?>
            <?php echo TREngine\Engine\Core\CoreMain::getInstance()->getDefaultSiteSlogan(); ?>
        </div>
        <div id="close_block">
            <div id="close_image"><a href="<?php echo "http://" . TR_ENGINE_URL; ?>"></a></div>
            <div id="close_text">
                <br /><?php echo $closeText; ?>
                <br /><br /><?php echo $closeReason; ?>
                <br />
                <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlock(); ?>
            </div>
        </div>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaFooters(); ?>
    </body>
</html>
