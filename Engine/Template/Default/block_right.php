<div class="block_top">
    <object type="application/x-shockwave-flash" data="<?php echo TREngine\Engine\Lib\LibMakeStyle::getTemplateDir(); ?>/images/block_top.swf" width="174px" height="57px">
        <param name="movie" value="<?php echo TREngine\Engine\Lib\LibMakeStyle::getTemplateDir(); ?>/images/block_top.swf" />
        <param name="pluginurl" value="http://www.macromedia.com/go/getflashplayer" />
        <param name="wmode" value="transparent" />
        <param name="menu" value="false" />
        <param name="quality" value="best" />
        <param name="scale" value="exactfit" />
        <param name="flashvars" value="menu_title=<?php echo $blockTitle; ?>" />
    </object>
</div>
<div class="block_middle">
    <div class="block_content">
        <?php echo $blockContent; ?>
    </div>
</div>
<div class="block_bottom"></div>