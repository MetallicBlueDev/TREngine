<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
    <head>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaHeaders(); ?>
        <link rel="stylesheet" href="<?php echo TREngine\Engine\Lib\LibMakeStyle::getTemplateDir(); ?>/style.css" type="text/css" />
        <link rel="stylesheet" href="<?php echo TREngine\Engine\Lib\LibMakeStyle::getTemplateDir(); ?>/style_close.css" type="text/css" />
        <?php if (TREngine\Engine\Core\CoreLoader::isCallable("CoreMain") && TREngine\Engine\Core\CoreMain::getInstance()->getAgentInfos()->getBrowserName() == "Internet Explorer" && TREngine\Engine\Core\CoreMain::getInstance()->getAgentInfos()->getBrowserVersion() < "7") { ?>
            <link rel="stylesheet" href="<?php echo TREngine\Engine\Lib\LibMakeStyle::getTemplateDir(); ?>/ie6.css" type="text/css" />
        <?php } ?>
    </head>
    <body>

        <div style="display: none;">
            <?php echo TREngine\Engine\Core\CoreMain::getInstance()->getDefaultSiteName(); ?>
            <br />
            <?php echo TREngine\Engine\Core\CoreMain::getInstance()->getDefaultSiteSlogan(); ?>
        </div>
        <div id="close_block"><br /><br /><br />
            <a href="<?php echo "http://" . TR_ENGINE_URL; ?>">
                <img src="<?php echo TREngine\Engine\Lib\LibMakeStyle::getTemplateDir(); ?>/images/tr_studio.png" alt="" title="" />
            </a>
            <div id="close_text">
                <br /><?php echo $closeText; ?>
                <br /><br /><?php echo TREngine\Engine\Core\CoreMain::getInstance()->getDefaultSiteCloseReason(); ?>
                <br />
                <?php echo TREngine\Engine\Lib\LibBlock::getInstance()->getBlock(); ?>
            </div>
        </div>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaFooters(); ?>
    </body>
</html>