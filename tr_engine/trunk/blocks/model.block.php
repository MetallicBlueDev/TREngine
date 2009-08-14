<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../engine/core/secure.class.php");
	new Core_Secure();
}

/**
 * Block de base, hérité par tous les autres blocks
 * 
 * @author Sebastien Villemain
 *
 */
class Block_Model {
	
	/**
	 * Identifiant du block
	 * 
	 * @var int
	 */
	public $blockId = 0;
	
	/**
	 * Position du block en chiffre
	 * 
	 * @var int
	 */
	public $side = 0;
	
	/**
	 * Position du block en lettre
	 * 
	 * @var String
	 */
	public $sideName = "";
	
	/**
	 * Nom complet du template de block a utiliser
	 * 
	 * @var String
	 */
	public $templateName = "";
	
	/**
	 * Titre du block
	 * 
	 * @var String
	 */
	public $title = "";
	
	/**
	 * Contenu du block
	 * 
	 * @var String
	 */
	public $content = "";
	
	/**
	 * Rang pour acceder au block
	 * 
	 * @var int
	 */
	public $rang = "";
	
	public function display() {
		Core_Exception::setMinorError(ERROR_BLOCK_IMPLEMENT . (($this->title != "") ? " (" . $this->title . ")" : ""));
	}
}


?>