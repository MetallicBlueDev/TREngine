<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../../engine/core/secure.class.php");
	new Core_Secure();
}

class Module_Home_Index extends Module_Model {
	
	public function display() {
?>
<div class="title">
	<span>Bonjour, bienvenue sur <a href="index.php">Trancer-Studio.net</a>.</span>
</div>
<br /><br />
<div class="description" style="width: 70%;">
	<span>Le site est un peu vide, mais il va se remplir petit &#224; petit...</span>
	<br /><br />A venir : des textes, de la documentation, des exemples, des images relatif &#224; mes projets.
</div>

<?php
	}
	
	public function setting() {
		return "Pas de setting...";
	}
}


?>