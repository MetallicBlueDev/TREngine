<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
    <head>
        <?php echo CoreHtml::getInstance()->getMetaHeaders(); ?>
        <link rel="stylesheet" href="templates/default/style.css" type="text/css" />
        <link rel="stylesheet" href="templates/default/style_close.css" type="text/css" />
        <?php if (CoreLoader::isCallable("Exec_Agent") && Exec_Agent::$userBrowserName == "Internet Explorer" && Exec_Agent::$userBrowserVersion < "7") { ?>
            <link rel="stylesheet" href="templates/default/ie6.css" type="text/css" />
        <?php } ?>
    </head>
    <body>

        <div style="display: none;">
            <?php echo CoreMain::getInstance()->getDefaultSiteName(); ?>
            <br />
            <?php echo CoreMain::getInstance()->getDefaultSiteSlogan(); ?>
        </div>
        <div id="close_block"><br /><br /><br />
            <a href="<?php echo "http://" . TR_ENGINE_URL; ?>">
                <img src="templates/default/images/tr_studio.png" alt="" title="" />
            </a>
            <div id="close_text">
                <br /><?php echo $closeText; ?>
                <br /><br /><?php echo CoreMain::getInstance()->getDefaultSiteCloseReason(); ?>
                <br />
                <?php echo LibsBlock::getInstance()->getBlock(); ?>
            </div>
        </div>
        <?php echo CoreHtml::getInstance()->getMetaFooters(); ?>
    </body>
</html>
