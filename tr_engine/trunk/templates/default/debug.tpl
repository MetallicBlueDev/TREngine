<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
    xml:lang="fr"
    lang="fr"
    dir="ltr">
<head>
<?php echo Core_Html::getInstance()->getMetaHeaders(); ?>
</head>
<body>

<b><?php echo $errorMessageTitle; ?></b>
<br /><br />

<?php if (!empty($errorMessage)) { ?>
Error details:<br />
<ul>
<?php foreach($errorMessage as $value) { ?>
<li><?php echo $value; ?></li>
<?php } ?>
</ul>
<?php } ?>

<?php echo Core_Html::getInstance()->getMetaFooters(); ?>
</body>
</html>