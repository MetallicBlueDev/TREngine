<!DOCTYPE html>
<html lang="fr">
    <head>
        <title><?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaTitle(); ?></title>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaHeaders(); ?>
    </head>
    <body>
        <div id="global">
            <header id="header">
                <div style="display: none;"><?php echo TREngine\Engine\Core\CoreMain::getInstance()->getDefaultSiteName(); ?></div>
                <div id="header_left">
                    <a href="index.php" title="<?php echo TREngine\Engine\Core\CoreMain::getInstance()->getDefaultSiteName(); ?>"></a>
                    <h3><?php echo TREngine\Engine\Core\CoreMain::getInstance()->getDefaultSiteSlogan(); ?></h3>
                </div>
                <div id="header_middle"></div>
                <div id="header_right"></div>
            </header>
            <div id="wrapper_leftfix">
                <div id="wrapper_rightfix">
                    <div id="wrapper_left">
                        <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBySideName("left"); ?>
                    </div>
                    <div id="wrapper_middlefix">
                        <div id="wrapper_middle">
                            <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getLoader(); ?>
                            <div id="breadcrumb">
                                <?php echo TREngine\Engine\Lib\LibBreadcrumb::getInstance()->getBreadcrumbTrail(" > "); ?>
                            </div>
                            <div class="cleaner"></div>
                            <?php TREngine\Engine\Core\CoreLogger::displayMessages(); ?>
                            <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBySideName("top"); ?>
                            <div>
                                <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBySideName("moduleleft"); ?>
                            </div>
                            <div>
                                <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBySideName("moduletop"); ?>
                            </div>
                            <div id="module_top">
                                <div id="module_top_left"></div>
                                <div id="module_top_middle"></div>
                                <div id="module_top_right"></div>
                            </div>
                            <div id="module_content_leftfix">
                                <div id="module_content_rightfix">
                                    <div id="module_content_middle">
                                        <?php echo TREngine\Engine\Lib\LibModule::getInstance()->getModule(); ?>
                                    </div>
                                </div>
                            </div>
                            <div id="module_bottom">
                                <div id="module_bottom_left"></div>
                                <div id="module_bottom_middle"></div>
                                <div id="module_bottom_right"></div>
                            </div>
                            <div>
                                <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBySideName("modulebottom"); ?>
                            </div>
                            <div>
                                <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBySideName("moduleright"); ?>
                            </div>
                            <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBySideName("bottom"); ?>
                        </div>
                    </div>
                    <div id="wrapper_right">
                        <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBySideName("right"); ?>
                    </div>
                </div>
            </div>
            <footer id="footer">
                <div id="footer_left"></div>
                <div id="footer_middle">
                    <div style="padding: 50px;">
                        Page g&eacute;n&eacute;r&eacute;e en <?php echo TREngine\Engine\Exec\ExecTimeMarker::getMeasurement("main"); ?> ms.
                    </div>
                </div>
                <div id="footer_right"></div>
            </footer>
        </div>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaFooters(); ?>
    </body>
</html>