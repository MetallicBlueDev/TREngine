<?php

function menuDisplay($configs) {
	/*
	echo "blockId : " . $configs['blockId'];
	echo "<br />side : " . $configs['side'];
	echo "<br />title : " . $configs['title'];
	echo "<br />content : " . $configs['content'];
	echo "<br />type : " . $configs['type'];
	echo "<br />rang : " . $configs['rang'];
	echo "<br />mods : ";
	print_r($configs['mods']);
	*/

	$libsMakeStyle = new Libs_MakeStyle();
	$libsMakeStyle->assign("blockTitle", $configs['title']);
	$libsMakeStyle->assign("blockContent", $configs['content']);
	$libsMakeStyle->display("block_" . Libs_Block::getInstance()->getSide($configs['side'], "letters") . ".tpl");
}


?>