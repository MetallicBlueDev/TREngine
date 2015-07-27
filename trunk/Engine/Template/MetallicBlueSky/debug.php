<!DOCTYPE html>
<html lang="fr">
    <head>
        <title><?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaTitle(); ?></title>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaHeaders(); ?>
    </head>
    <body>
        <div class="debugerror">
            <span class="text_bold"><?php echo $errorMessageTitle; ?></span>
            <div>
                <br /><br />
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
            </div>
        </div>
        <?php echo TREngine\Engine\Core\CoreHtml::getInstance()->getMetaFooters(); ?>
    </body>
</html>