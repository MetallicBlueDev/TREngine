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
<br /><br />

TEMPLATE :<br />
Dossier conteneur de templates : <?php echo Libs_MakeStyle::getTemplatesDir(); ?><br />
Nom du template courant: <?php echo Libs_MakeStyle::getTemplateUsedDir(); ?>
<br /><br />

CONTENU BLOCK :<br />
<?php echo Libs_Block::getInstance()->getBlocks("right"); ?>
<br /><br />
CONTENU MODULE :<br /><?php echo Libs_Module::getInstance()->getModule(); ?>
<br /><br />

MARKER :<br />
Timer MAIN : <?php echo Exec_Marker::getTime("main"); ?>

<?php echo Core_Html::getInstance()->getMetaFooters(); ?>
</body>
</html>