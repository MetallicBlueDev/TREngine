<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
    <head>
        <?php echo CoreHtml::getInstance()->getMetaHeaders(); ?>
        <link rel="stylesheet" href="templates/default/style.css" type="text/css" />
        <?php if (CoreLoader::isCallable("ExecAgent") && ExecAgent::$userBrowserName == "Internet Explorer" && ExecAgent::$userBrowserVersion < "7") { ?>
            <link rel="stylesheet" href="templates/default/ie6.css" type="text/css" />
        <?php } ?>
    </head>
    <body>

        <div id="header">
            <div style="display: none;"><h2><?php echo CoreMain::getInstance()->getDefaultSiteName() . " - " . CoreMain::getInstance()->getDefaultSiteSlogan(); ?></h2></div>
            <object type="application/x-shockwave-flash" data="templates/default/images/header.swf" width="900px" height="130px">
                <param name="movie" value="templates/default/images/header.swf" />
                <param name="pluginurl" value="http://www.macromedia.com/go/getflashplayer" />
                <param name="wmode" value="transparent" />
                <param name="menu" value="false" />
                <param name="quality" value="best" />
                <param name="scale" value="exactfit" />
            </object>
        </div>

        <div id="wrapper">
            <div id="left">

                <?php echo LibBlock::getInstance()->getBlocksBySideName("right"); ?>

            </div>

            <div id="middle">

                <?php echo CoreHtml::getInstance()->getLoader(); ?>

                <div id="breadcrumb"><?php echo LibBreadcrumb::getInstance()->getBreadcrumbTrail(" >> "); ?></div>

                <div class="cleaner"></div>

                <?php CoreLogger::displayMessages(); ?>

                <?php echo LibBlock::getInstance()->getBlocksBySideName("top"); ?>

                <?php include(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "default" . DIRECTORY_SEPARATOR . "module.php"); ?>

                <?php echo LibBlock::getInstance()->getBlocksBySideName("bottom"); ?>

            </div>
        </div>

        <div id="footer">
            <div style="padding: 50px;">
                Page g&eacute;n&eacute;r&eacute;e en <?php echo ExecTimeMarker::getMeasurement("main"); ?> ms.
            </div>
        </div>
        <?php echo CoreHtml::getInstance()->getMetaFooters(); ?>
    </body>
</html>