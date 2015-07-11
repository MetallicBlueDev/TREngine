<!DOCTYPE html>
<html lang="fr">
    <head>
        <title><?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaTitle(); ?></title>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaHeaders(); ?>
        <link rel="stylesheet" href="<?php echo TREngine\Engine\Lib\LibMakeStyle::getTemplateDir(); ?>/style.css" type="text/css" />
        <?php if (TREngine\Engine\Core\CoreLoader::isCallable("CoreMain") && TREngine\Engine\Core\CoreMain::getInstance()->getAgentInfos()->getBrowserName() == "Internet Explorer" && TREngine\Engine\Core\CoreMain::getInstance()->getAgentInfos()->getBrowserVersion() < "7") { ?>
            <link rel="stylesheet" href="<?php echo TREngine\Engine\Lib\LibMakeStyle::getTemplateDir(); ?>/ie6.css" type="text/css" />
        <?php } ?>
    </head>
    <body>
        <div id="global">
            <div id="header">
                <div style="display: none;"><?php echo TREngine\Engine\Core\CoreMain::getInstance()->getDefaultSiteName(); ?></div>
                <div id="header_left">
                    <a href="index.php" title="<?php echo TREngine\Engine\Core\CoreMain::getInstance()->getDefaultSiteName(); ?>"></a>
                    <h3><?php echo TREngine\Engine\Core\CoreMain::getInstance()->getDefaultSiteSlogan(); ?></h3>
                </div>
                <div id="header_middle"></div>
                <div id="header_right"></div>
            </div>
            <div id="wrapper">
                <div id="left">
                    <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBySideName("right"); ?>
                </div>
                <div id="middle">
                    <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getLoader(); ?>
                    <div id="breadcrumb"><?php echo TREngine\Engine\Lib\LibBreadcrumb::getInstance()->getBreadcrumbTrail(" >> "); ?></div>
                    <div class="cleaner"></div>
                    <?php TREngine\Engine\Core\CoreLogger::displayMessages(); ?>
                    <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBySideName("top"); ?>
                    <?php include TREngine\Engine\Lib\LibMakeStyle::getTemplateDir() . DIRECTORY_SEPARATOR . "module.php"; ?>
                    <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBySideName("bottom"); ?>
                </div>
            </div>
            <div id="footer">
                <div style="padding: 50px;">
                    Page g&eacute;n&eacute;r&eacute;e en <?php echo TREngine\Engine\Exec\ExecTimeMarker::getMeasurement("main"); ?> ms.
                </div>
            </div>
        </div>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaFooters(); ?>
    </body>
</html>