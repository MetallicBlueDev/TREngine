<?php
if (!defined("TR_ENGINE_INDEX")) {
	require("../core/secure.class.php");
	new Core_Secure();
}

/**
 * Gestionnaire de blocks
 * 
 * @author Sebastien Villemain
 *
 */
class Libs_Block {
	
	/**
	 * Gestionnnaire de blocks
	 * 
	 * @var Libs_Block
	 */
	private static $block = false;
	
	/**
	 * Blocks chargés
	 * 
	 * @var array
	 */
	private $blocksLoaded = array();
	
	public function __construct() {
		$this->load();
	}
	
	/**
	 * Instance du gestionnaire de block
	 * 
	 * @return Libs_Block
	 */
	public static function getInstance() {
		if (!self::$block) {
			self::$block = new self();
		}
		return self::$block;
	}
	
	/**
	 * Vérifie si le block est valide
	 * 
	 * @param $blockType String
	 * @return boolean true block valide
	 */
	private function isBlock($blockType) {
		return is_file(TR_ENGINE_DIR . "/blocks/" . $blockType . ".block.php");
	}
	
	/**
	 * Vérifie si le block doit être activé
	 * 
	 * @param $modules array
	 * @return boolean true le block doit être actif
	 */
	private function blockActiveMod($modules = array("all")) {
		foreach ($modules as $modSelected)  {
			if (Core_Main::$module == $modSelected || $modSelect == "all") {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Retourne un tableau de donnée
	 * 
	 * @param $content String
	 * @return array
	 */
	private function arrayContent($content = "") {
		return explode("|", $content);
	}
	
	/**
	 * Charge les blocks
	 */
	private function load() {
		$sql = Core_Sql::getInstance();
		$sql->select(
			Core_Table::$BLOCKS_TABLE,
			array("block_id", "side", "position", "title", "content", "type", "rang", "mod"),
			array("side > 0", "&& rang >= 0"),
			array("side", "position")
		);
		
		// Récuperation des données des blocks
		$blocksLoaded = array();
		while ($block = $sql->fetchArray()) {
			$block['mod'] = $this->arrayContent($block['mod']);
			
			if ($this->isBlock($block['type']) // Si le block existe
					&& $this->blockActiveMod($block['mod']) // Et qu'il est actif sur la page courante
					&& Core_Session::$userRang >= $block['rang']) { // Et que le client est assez gradé
				$this->get($block);
			}
		}
	}
	
	/**
	 * Récupère le block
	 * 
	 * @param $block array
	 */
	private function get($block) {
		// On prepare le contenu du block suivant le type
		$block['title'] = Exec_Entities::textDisplay($block['title']);
		Core_Loader::blockLoader($block['type']);
		$functionBlockDisplay = $block['type'] . "Display";
		
		// Capture des données d'affichage
		ob_start();
		$functionBlockDisplay($block, $side);
		$this->blocksLoaded[$block['side']][] = ob_get_contents();
		ob_end_clean();
	}
	
	/**
	 * Retourne le contenu du block voulu (right/left/top/bottom)
	 * 
	 * @param $side String
	 * @return String
	 */
	public function getBlock($side) {
		// Recherche de la position
		$side = strtolower($side);
		switch ($side) {
			case 'right': $side = 1; break;
			case 'left': $side = 2; break;
			case 'top': $side = 3; break;
			case 'bottom': $side = 4; break;
			default : Core_Secure::getInstance()->debug("blockSide");
		}
		
		$blockSide = "";
		foreach($this->blocksLoaded[$side] as $block) {
			$blockSide .= $block;
		}
		return $blockSide;
	}
}


?>