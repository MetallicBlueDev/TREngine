<?php

function testDisplay($configs) {
	echo "blockId : " . $configs['blockId'];
	echo "<br />side : " . $configs['side'];
	echo "<br />title : " . $configs['title'];
	echo "<br />content : " . $configs['content'];
	echo "<br />type : " . $configs['type'];
	echo "<br />rang : " . $configs['rang'];
	echo "<br />mods : ";
	print_r($configs['mods']);
}


?>