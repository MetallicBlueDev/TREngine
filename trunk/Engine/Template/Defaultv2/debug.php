<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
    <head>
        <title><?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaTitle(); ?></title>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaHeaders(); ?>
        <link rel="stylesheet" href="<?php echo TREngine\Engine\Lib\LibMakeStyle::getTemplateDir(); ?>/style.css" type="text/css" />
    </head>
    <body>
        <div class="debugerror">
            <span class="text_bold"><?php echo $errorMessageTitle; ?></span>
            <br /><br />

            <?php if (!empty($errorMessage)) { ?>
                Error details:<br />
                <ul>
                    <?php foreach ($errorMessage as $value) { ?>
                        <?php if (empty($value)) { ?>
                            <li><hr/></li>
                        <?php } else { ?>
                            <li><?php echo $value; ?></li>
                            <?php
                        }
                    }
                    ?>
                </ul>
            <?php } ?>
        </div>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaFooters(); ?>
    </body>
</html>