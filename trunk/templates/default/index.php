<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
    <head>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaHeaders(); ?>
        <link rel="stylesheet" href="templatestest/default/style.css" type="text/css" />
        <?php if (TREngine\Engine\Core\CoreLoader::isCallable("CoreMain") && TREngine\Engine\Core\CoreMain::getInstance()->getAgentInfos()->getBrowserName() == "Internet Explorer" && TREngine\Engine\Core\CoreMain::getInstance()->getAgentInfos()->getBrowserVersion() < "7") { ?>
            <link rel="stylesheet" href="templatestest/default/ie6.css" type="text/css" />
        <?php } ?>
    </head>
    <body>

        <div id="header">
            <div style="display: none;"><h2><?php echo TREngine\Engine\Core\CoreMain::getInstance()->getDefaultSiteName() . " - " . TREngine\Engine\Core\CoreMain::getInstance()->getDefaultSiteSlogan(); ?></h2></div>
            <object type="application/x-shockwave-flash" data="templatestest/default/images/header.swf" width="900px" height="130px">
                <param name="movie" value="templatestest/default/images/header.swf" />
                <param name="pluginurl" value="http://www.macromedia.com/go/getflashplayer" />
                <param name="wmode" value="transparent" />
                <param name="menu" value="false" />
                <param name="quality" value="best" />
                <param name="scale" value="exactfit" />
            </object>
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

                <?php include TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . "templatestest" . DIRECTORY_SEPARATOR . "default" . DIRECTORY_SEPARATOR . "module.php"; ?>

                <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlocksBySideName("bottom"); ?>

            </div>
        </div>

        <div id="footer">
            <div style="padding: 50px;">
                Page g&eacute;n&eacute;r&eacute;e en <?php echo TREngine\Engine\Exec\ExecTimeMarker::getMeasurement("main"); ?> ms.
            </div>
        </div>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaFooters(); ?>
    </body>
</html>