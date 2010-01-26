<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
<?php echo Core_Html::getInstance()->getMetaHeaders(); ?>
<link rel="stylesheet" href="templates/default/style.css" type="text/css" />
<?php if (Core_Loader::isCallable("Exec_Agent") && Exec_Agent::$userBrowserName == "Internet Explorer" && Exec_Agent::$userBrowserVersion < "7") { ?>
<link rel="stylesheet" href="templates/default/ie6.css" type="text/css" />
<?php } ?>
</head>
<body>

<div id="header">
<div style="display: none;"><h2><?php echo Core_Main::$coreConfig['defaultSiteName'] . " - " . Core_Main::$coreConfig['defaultSiteSlogan']; ?></h2></div>
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
	
	<?php echo Libs_Block::getInstance()->getBlocks("right"); ?>
	
	</div>
	
	<div id="middle">
	
	<?php echo Core_Html::getInstance()->getLoader(); ?>
	
	<div id="breadcrumb"><?php echo Libs_Breadcrumb::getInstance()->getBreadcrumbTrail(" >> "); ?></div>
	
	<div class="cleaner"></div>
	
	<?php echo Core_Exception::getMinorError(); ?>
	
	<?php echo Libs_Block::getInstance()->getBlocks("top"); ?>
	
	<?php include(TR_ENGINE_DIR . "/templates/default/module.tpl"); ?>
	
	<?php echo Libs_Block::getInstance()->getBlocks("bottom"); ?>
	
	</div>
</div>

<div id="footer">
	<div style="padding: 50px;">
		Page g&eacute;n&eacute;r&eacute;e en <?php echo Exec_Marker::getTime("main"); ?> seconde.
	</div>
</div>
<?php echo Core_Html::getInstance()->getMetaFooters(); ?>
</body>
</html>