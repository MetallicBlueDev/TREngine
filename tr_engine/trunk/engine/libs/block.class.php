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
	 * Blocks charg�s, tableau a deux dimensions
	 * 
	 * @var array
	 */
	public static $blocksConfig = array();
	
	/**
	 * Blocks compil�s, tableau a deux dimensions
	 * 
	 * @var array
	 */
	private $blocksCompiled = array();
	
	public function __construct() {
		Core_Loader::classLoader("Block_Model");
	}
	
	/**
	 * Instance du gestionnaire de block
	 * 
	 * @return Libs_Block
	 */
	public static function &getInstance() {
		if (self::$libsBlock === false) {			
			self::$libsBlock = new self();
		}
		return self::$libsBlock;
	}
	
	/**
	 * V�rifie si le block est valide
	 * 
	 * @param $blockType String
	 * @return boolean true block valide
	 */
	private function isBlock($blockType) {
		return is_file(TR_ENGINE_DIR . "/blocks/" . $blockType . ".block.php");
	}
	
	/**
	 * V�rifie si le block doit �tre activ�
	 * 
	 * @param $modules array
	 * @return boolean true le block doit �tre actif
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
		Core_Sql::select(
			Core_Table::$BLOCKS_TABLE,
			array("block_id", "side", "position", "title", "content", "type", "rang", "mods"),
			array("side > 0", "&& rang >= 0"),
			array("side", "position")
		);
		
		if (Core_Sql::affectedRows() > 0) {
			// R�cuperation des donn�es des blocks
			Core_Sql::addBuffer("block");
			while ($block = Core_Sql::fetchBuffer("block")) {
				$block->mods = explode("|", $block->mods);
				
				if ($this->isBlock($block->type) // Si le block existe
						&& $this->blockActiveMod($block->mods) // Et qu'il est actif sur la page courante
						&& Core_Session::$userRang >= $block->rang) { // Et que le client est assez grad�
					$block->title = Exec_Entities::textDisplay($block->title);
					
					self::$blocksConfig[$block->side][] = $block;
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
		$blockId = Core_Request::getInt("block");
		
		if (is_numeric($blockId)) {
			Core_Sql::select(
				Core_Table::$BLOCKS_TABLE,
				array("block_id", "side", "position", "title", "content", "type", "rang", "mods"),
				array("block_id = '" . $blockId . "'")
			);
			
			if (Core_Sql::affectedRows() > 0) {
				$block = Core_Sql::fetchObject();
				
				if ($this->isBlock($block->type) // Si le block existe
						&& Core_Session::$userRang >= $block->rang) { // Et que le client est assez grad�
					$block->title = Exec_Entities::textDisplay($block->title);
					
					self::$blocksConfig[$block->side][] = $block;
					$this->get($block);
				}
			}
		}
	}
	
	/**
	 * R�cup�re le block
	 * 
	 * @param $block array
	 */
	private function get($block) {
		$blockClassName = "Block_" . ucfirst($block->type);
		$loaded = Core_Loader::classLoader($blockClassName);
		
		// V�rification du block
		if ($loaded) {
			if (Core_Loader::isCallable($blockClassName, "display")) {
				// V�rification de l'acc�s
				if (Core_Acces::autorize("block" . $block->block_id, $block->rang)) {
					$BlockClass = new $blockClassName();
					$BlockClass->blockId = $block->block_id;
					$BlockClass->side = $block->side;
					$BlockClass->sideName = $this->getSide($block->side, "letters");
					$BlockClass->templateName = "block_" . $BlockClass->sideName . ".tpl";
					$BlockClass->title = $block->title;
					$BlockClass->content = $block->content;
					$BlockClass->rang = $block->rang;
					
					// Capture des donn�es d'affichage
					ob_start();
					$BlockClass->display();
					$this->blocksCompiled[$block->side][] = ob_get_contents();
					ob_end_clean();
				}
			} else {
				Core_Exception::setMinorError(ERROR_BLOCK_CODE);
			}
		}
	}
	
	/**
	 * Retourne les blocks compil�s voulu (right/left/top/bottom)
	 * 
	 * @param $side String or int
	 * @return String
	 */
	public function getBlocks($side) {
		$side = $this->getSide($side, "numeric");
		
		$blockSide = "";
		if (isset($this->blocksCompiled[$side])) {
			foreach($this->blocksCompiled[$side] as $key => $block) {
				$blockSide .= $this->outPut($block, $this->doRewriteBuffer($side, $key));
			}
		}
		return $blockSide;
	}
	
	/**
	 * Retourne la position associ�
	 * 
	 * @param $side String or int
	 * @return String or int
	 */
	private function getSide($side, $type = "numeric") {
		if ($type == "letters") {
			switch ($side) {
				case 1: $side = "right"; break;
				case 2: $side = "left"; break;
				case 3: $side = "top"; break;
				case 4: $side = "bottom"; break;
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
					default : Core_Secure::getInstance()->debug("blockSide");
				}
			}
		}
		return $side;
	}
	
	/**
	 * R��criture du tampon de sortie si besoin
	 * 
	 * @param $buffer String
	 * @return $buffer String
	 */
	private function outPut($buffer, $rewriteBuffer = false) {
		if (Core_Main::doUrlRewriting() && $rewriteBuffer) {
			$buffer = Core_UrlRewriting::rewriteBuffer($buffer);
		}
		return $buffer;
	}
	
	/**
	 * Recherche le parametre indiquand qu'il doit y avoir une r��criture du buffer
	 * 
	 * @param $side int cot� ou se trouve le block
	 * @param $key int cles du block
	 * @return boolean true il doit y avoir r��criture
	 */
	private function doRewriteBuffer($side, $key) {
		return (strpos(self::$blocksConfig[$side][$key]->content, "rewriteBuffer") !== false) ? true : false;
	}
	
	/**
	 * Retourne le block compil�
	 * 
	 * @return String
	 */
	public function getBlock() {
		if (Core_Main::isBlockScreen()) {
			foreach($this->blocksCompiled as $side => $compiled) {
				return $this->outPut($this->blocksCompiled[$side][0], $this->doRewriteBuffer($side, 0));
			}
		} else {
			Core_Secure::getInstance()->debug("blockDisplay");
		}
	}
}


?>