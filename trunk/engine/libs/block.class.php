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
	private static $libsBlock = null;
	
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
	public static function &getInstance() {
		if (self::$libsBlock == null) {			
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
		if (Core_Loader::isCallable("Libs_Module")) {
			foreach ($modules as $modSelected)  {
				if (Libs_Module::$module == $modSelected || $modSelected == "all") {
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Charge les blocks
	 */
	// TODO mettre en cache la requete
	public function launchAllBlock() {
		Core_Sql::select(
			Core_Table::$BLOCKS_TABLE,
			array("block_id", "side", "position", "title", "content", "type", "rang", "mods"),
			array("side > 0", "&& rang >= 0"),
			array("side", "position")
		);
		
		if (Core_Sql::affectedRows() > 0) {
			// Récuperation des données des blocks
			Core_Sql::addBuffer("block");
			while ($block = Core_Sql::fetchBuffer("block")) {
				$block->mods = explode("|", $block->mods);
				
				if ($this->isBlock($block->type) // Si le block existe
						&& $this->blockActiveMod($block->mods) // Et qu'il est actif sur la page courante
						&& Core_Session::$userRang >= $block->rang) { // Et que le client est assez gradé
					$block->title = Exec_Entities::textDisplay($block->title);
					
					self::$blocksConfig[$block->side][] = $block;
					$this->get($block);
				}
			}
		}
	}
	
	/**
	 * Charge un block
	 * 
	 * @param $where array exemple array(block_id = '0')
	 */
	// TODO mettre en cache la requete
	public function launchOneBlock($where = array()) {
		if (empty($where)) { // Capture de la variable
			$where = array("block_id = '" . Core_Request::getInt("block") . "'");
		}
		
		Core_Sql::select(
			Core_Table::$BLOCKS_TABLE,
			array("block_id", "side", "position", "title", "content", "type", "rang", "mods"),
			$where
		);
		if (Core_Sql::affectedRows() > 0) {
			$block = Core_Sql::fetchObject();
			
			if ($this->isBlock($block->type) // Si le block existe
					&& Core_Session::$userRang >= $block->rang) { // Et que le client est assez gradé
				$block->title = Exec_Entities::textDisplay($block->title);
				
				self::$blocksConfig[$block->side][] = $block;
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
		// Vérification de l'accès
		if (Core_Access::autorize("block" . $block->block_id, $block->rang)) {
			$blockClassName = "Block_" . ucfirst($block->type);
			$loaded = Core_Loader::classLoader($blockClassName);
			
			// Vérification du block
			if ($loaded) {
				if (Core_Loader::isCallable($blockClassName, "display")) {
					Core_Translate::translate("blocks/" . $block->type);
					$BlockClass = new $blockClassName();
					$BlockClass->blockId = $block->block_id;
					$BlockClass->side = $block->side;
					$BlockClass->sideName = self::getSide($block->side, "letters");
					$BlockClass->templateName = "block_" . $BlockClass->sideName . ".tpl";
					$BlockClass->title = $block->title;
					$BlockClass->content = $block->content;
					$BlockClass->rang = $block->rang;
					
					// Capture des données d'affichage
					ob_start();
					$BlockClass->display();
						$this->blocksCompiled[$block->side][] = ob_get_contents();
						ob_end_clean();
				} else {
					Core_Exception::addAlertError(ERROR_BLOCK_CODE);
				}
			}
		}
	}
	
	/**
	 * Retourne les blocks compilés voulu (right/left/top/bottom)
	 * 
	 * @param $side String or int
	 * @return String
	 */
	public function &getBlocks($side) {
		$side = self::getSide($side, "numeric");
		
		$blockSide = "";
		if (isset($this->blocksCompiled[$side])) {
			foreach($this->blocksCompiled[$side] as $key => $block) {
				$blockSide .= $this->outPut($block, $this->doRewriteBuffer($side, $key));
			}
		}
		return $blockSide;
	}
	
	/**
	 * Retourne la position associé
	 * 
	 * @param $side String or int
	 * @return String or int
	 */
	public static function &getSide($side, $type = "numeric") {
		if ($type == "letters") {
			switch ($side) {
				case 1: $side = "right"; break;
				case 2: $side = "left"; break;
				case 3: $side = "top"; break;
				case 4: $side = "bottom"; break;
				case 5: $side = "moduletop"; break;
				case 6: $side = "modulebottom"; break;
				default : Core_Secure::getInstance()->debug("blockSide");
			}
		} else {
			if (!is_numeric($side) 
					|| $side < 0
					|| $side > 4) {
				// Recherche de la position
				$side = strtolower($side);
				switch ($side) {
					case 'right': $side = 1; break;
					case 'left': $side = 2; break;
					case 'top': $side = 3; break;
					case 'bottom': $side = 4; break;
					case 'moduletop': $side = 5; break;
					case 'modulebottom': $side = 6; break;
					default : Core_Secure::getInstance()->debug("blockSide");
				}
			}
		}
		return $side;
	}
	
	/**
	 * Retourne le type d'orientation
	 * 
	 * @param $side String
	 */
	public static function &getLitteralSide($side) {
		$side = strtoupper(self::getSide($side, "letters"));
		$side = defined($side) ? constant($side) : $side;
		return $side;
	}
	
	/**
	 * Réécriture du tampon de sortie si besoin
	 * 
	 * @param $buffer String
	 * @return $buffer String
	 */
	private function &outPut(&$buffer, $rewriteBuffer = false) {
		if (Core_Main::doUrlRewriting() && $rewriteBuffer) {
			$buffer = Core_UrlRewriting::rewriteBuffer($buffer);
		}
		return $buffer;
	}
	
	/**
	 * Recherche le parametre indiquant qu'il doit y avoir une réécriture du buffer
	 * 
	 * @param $side int coté ou se trouve le block
	 * @param $key int cles du block
	 * @return boolean true il doit y avoir réécriture
	 */
	private function doRewriteBuffer($side, $key) {
		return (strpos(self::$blocksConfig[$side][$key]->content, "rewriteBuffer") !== false) ? true : false;
	}
	
	/**
	 * Retourne le block compilé
	 * 
	 * @return String
	 */
	public function getBlock() {
		foreach($this->blocksCompiled as $side => $compiled) {
			return $this->outPut($this->blocksCompiled[$side][0], $this->doRewriteBuffer($side, 0));
		}
	}
}

/**
 * Block de base, hérité par tous les autres blocks
 * Modèle pour le contenu d'un block
 * 
 * @author Sebastien Villemain
 *
 */
abstract class Block_Model {
	
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
	
	/**
	 * Affichage par défaut
	 */
	public function display() {
		Core_Exception::addAlertError(ERROR_BLOCK_IMPLEMENT . ((!empty($this->title)) ? " (" . $this->title . ")" : ""));
	}
}
?>