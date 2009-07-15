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
	private static $libsBlock = false;
	
	/**
	 * Blocks chargés, tableau a deux dimensions
	 * 
	 * @var array
	 */
	public static $blocksConfig = array();
	
	/**
	 * Blocks compilés, tableau a deux dimensions
	 * 
	 * @var array
	 */
	private $blocksCompiled = array();
	
	public function __construct() {
	}
	
	/**
	 * Instance du gestionnaire de block
	 * 
	 * @return Libs_Block
	 */
	public static function getInstance() {
		if (!self::$libsBlock) {
			self::$libsBlock = new self();
		}
		return self::$libsBlock;
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
			if (Libs_Module::$module == $modSelected || $modSelected == "all") {
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
	private function arrayContent($content) {
		return explode("|", $content);
	}
	
	/**
	 * Execute la routine block
	 */
	public function launch() {
		if (Core_Main::isBlockScreen()) {
			// Chargement de tout les blocks
			$this->launchOneBlock();
		} else {
			// Chargement d'un seul block
			$this->launchAllBlock();
		}
	}
	
	/**
	 * Charge les blocks
	 */
	private function launchAllBlock() {
		$sql = Core_Sql::getInstance();
		$sql->select(
			Core_Table::$BLOCKS_TABLE,
			array("block_id", "side", "position", "title", "content", "type", "rang", "mods"),
			array("side > 0", "&& rang >= 0"),
			array("side", "position")
		);
		
		if ($sql->affectedRows() > 0) {
			// Récuperation des données des blocks
			while (list($block['blockId'], $block['side'], $block['position'], $block['title'], $block['content'], $block['type'], $block['rang'], $block['mods']) = $sql->fetchArray()) {
				$block['mods'] = $this->arrayContent($block['mods']);
				
				if ($this->isBlock($block['type']) // Si le block existe
						&& $this->blockActiveMod($block['mods']) // Et qu'il est actif sur la page courante
						&& Core_Session::$userRang >= $block['rang']) { // Et que le client est assez gradé
					$block['title'] = Exec_Entities::textDisplay($block['title']);
					
					self::$blocksConfig[$block['side']][] = $block;
					$this->get($block);
				}
			}
		}
	}
	
	/**
	 * Charge un block
	 */
	private function launchOneBlock() {
		// Capture de la variable
		$blockId = Core_Secure::checkVariable("block");
		
		if (is_numeric($blockId)) {
			$sql = Core_Sql::getInstance();
			$sql->select(
				Core_Table::$BLOCKS_TABLE,
				array("side", "position", "title", "content", "type", "rang", "mods"),
				array("block_id = '" . $blockId . "'")
			);
			
			if ($sql->affectedRows() > 0) {
				$block = $sql->fetchArray();
				$block['blockId'] = $blockId;
				
				if ($this->isBlock($block['type']) // Si le block existe
						&& Core_Session::$userRang >= $block['rang']) { // Et que le client est assez gradé
					$block['title'] = Exec_Entities::textDisplay($block['title']);
					
					self::$blocksConfig[$block['side']][] = $block;
					$this->get($block);
				}
			}
		}
	}
	
	/**
	 * Récupère le block
	 * 
	 * @param $block array
	 */
	private function get($block) {
		Core_Loader::blockLoader($block['type']);
		$functionBlockDisplay = $block['type'] . "Display";
		
		// Capture des données d'affichage
		ob_start();
		$functionBlockDisplay($block);
		$this->blocksCompiled[$block['side']][] = ob_get_contents();
		ob_end_clean();
	}
	
	/**
	 * Retourne les blocks compilés voulu (right/left/top/bottom)
	 * 
	 * @param $side String
	 * @return String
	 */
	public function getBlocks($side) {
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
		foreach($this->blocksCompiled[$side] as $block) {
			$blockSide .= $block;
		}
		return $blockSide;
	}
	
	/**
	 * Retourne le block compilé
	 * 
	 * @return String
	 */
	public function getBlock() {
		if (Core_Main::isBlockScreen()) {
			foreach(self::$blocksConfig as $key => $block) {
				return $this->blocksCompiled[$key][0];
			}
		} else {
			Core_Secure::getInstance()->debug("blockDisplay");
		}
	}
}


?>