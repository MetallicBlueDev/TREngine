<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

Core_Loader::classLoader("Libs_BlockModel");

/**
 * Gestionnaire de blocks.
 *
 * @author Sébastien Villemain
 */
class Libs_Block {

    /**
     * Gestionnnaire de blocks.
     *
     * @var Libs_Block
     */
    private static $libsBlock = null;

    /**
     * Blocks chargés, tableau à deux dimensions.
     *
     * @var array
     */
    public static $blocksConfig = array();

    /**
     * Blocks compilés, tableau à deux dimensions.
     *
     * @var array
     */
    private $blocksCompiled = array();

    private function __construct() {

    }

    /**
     * Instance du gestionnaire de block.
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
     * Charge les blocks.
     */
    // TODO mettre en cache la requete
    public function launchAllBlock() {
        Core_Sql::select(
        Core_Table::$BLOCKS_TABLE, array(
            "block_id",
            "side",
            "position",
            "title",
            "content",
            "type",
            "rank",
            "mods"), array(
            "side > 0",
            "&& rank >= 0"), array(
            "side",
            "position")
        );

        if (Core_Sql::affectedRows() > 0) {
            // Récuperation des données des blocks
            Core_Sql::addBuffer("block");

            while ($block = Core_Sql::fetchBuffer("block")) {
                $block->mods = explode("|", $block->mods);

                if ($this->isBlock($block->type) // Si le block existe
                && $this->blockActiveMod($block->mods) // Et qu'il est actif sur la page courante
                && Core_Session::$userRank >= $block->rank) { // Et que le client est assez gradé
                    $block->title = Exec_Entities::textDisplay($block->title);

                    self::$blocksConfig[$block->side][] = $block;
                    $this->get($block);
                }
            }
        }
    }

    /**
     * Charge un block.
     *
     * @param array $where exemple array(block_id = '0').
     */
    // TODO mettre en cache la requete
    public function launchOneBlock(array $where = array()) {
        if (empty($where)) { // Capture de la variable
            $where = array(
                "block_id = '" . Core_Request::getInt("blockId") . "'");
        }

        Core_Sql::select(
        Core_Table::$BLOCKS_TABLE, array(
            "block_id",
            "side",
            "position",
            "title",
            "content",
            "type",
            "rank",
            "mods"), $where
        );
        if (Core_Sql::affectedRows() > 0) {
            $block = Core_Sql::fetchObject();

            if ($this->isBlock($block->type) // Si le block existe
            && Core_Session::$userRank >= $block->rank) { // Et que le client est assez gradé
                $block->title = Exec_Entities::textDisplay($block->title);

                self::$blocksConfig[$block->side][] = $block;
                $this->get($block);
            }
        }
    }

    /**
     * Retourne les blocks compilés voulu (right/left/top/bottom).
     *
     * @param string $side
     * @return string
     */
    public function &getBlocks($side) {
        // Conversion du side en identifiant numérique
        $sidePosition = is_string($side) ? self::getSideNumeric($side) : $side;

        $blockSide = "";
        if (isset($this->blocksCompiled[$sidePosition])) {
            foreach ($this->blocksCompiled[$sidePosition] as $key => $block) {
                $blockSide .= $this->getBlockBuffer($block, $sidePosition, $key);
            }
        }
        return $blockSide;
    }

    /**
     * Liste des orientations possible.
     *
     * @return array array("numeric" => identifiant int, "letters" => nom de la position).
     */
    public static function &listSide() {
        $sideList = array();

        for ($i = 1; $i < 7; $i++) {
            $sideList[] = array(
                "numeric" => $i,
                "letters" => self::getLitteralSide($i));
        }
        return $sideList;
    }

    /**
     * Retourne le type d'orientation avec la traduction.
     *
     * @param string $side
     * @return string postion traduit (si possible).
     */
    public static function &getLitteralSide($side) {
        $side = strtoupper(self::getSideLetters($side));
        $side = defined($side) ? constant($side) : $side;
        return $side;
    }

    /**
     * Retourne le block compilé.
     *
     * @return string
     */
    public function &getBlock() {
        $buffer = "";

        foreach ($this->blocksCompiled as $side => $compiled) {
            $buffer = $this->getBlockBuffer($compiled[0], $side, 0);
            break;
        }
        return $buffer;
    }

    /**
     * Retourne la liste des blocks disponibles.
     *
     * @return array
     */
    public static function &listBlocks() {
        $blockList = array();

        $files = Core_CacheBuffer::listNames("blocks");

        foreach ($files as $fileName) {
            // Nettoyage du nom de la page
            $pos = strpos($fileName, ".block");

            // Si c'est un block
            if ($pos !== false && $pos > 0) {
                $blockList[] = substr($fileName, 0, $pos);
            }
        }
        return $blockList;
    }

    /**
     * Vérifie si le block est valide.
     *
     * @param string $blockType
     * @return boolean true block valide
     */
    private function isBlock($blockType) {
        return is_file(TR_ENGINE_DIR . "/blocks/" . $blockType . ".block.php");
    }

    /**
     * Vérifie si le block doit être activé.
     *
     * @param array $modules
     * @return boolean true le block doit être actif.
     */
    private function &blockActiveMod(array $modules = array(
        "all")) {
        $rslt = false;

        if (Core_Loader::isCallable("Libs_Module")) {
            foreach ($modules as $modSelected) {
                if ($modSelected == "all" || Libs_Module::isSelected($modSelected)) {
                    $rslt = true;
                    break;
                }
            }
        }
        return $rslt;
    }

    /**
     * Récupère le block.
     *
     * @param array - object $block
     */
    private function get($block) {
        // Vérification de l'accès
        if (Core_Access::autorize("block" . $block->block_id, $block->rank)) {
            $blockClassName = "Block_" . ucfirst($block->type);
            $loaded = Core_Loader::classLoader($blockClassName);

            // Vérification du block
            if ($loaded) {
                if (Core_Loader::isCallable($blockClassName, "display")) {
                    Core_Translate::getInstance()->translate("blocks/" . $block->type);

                    $BlockClass = new $blockClassName();
                    $BlockClass->blockId = $block->block_id;
                    $BlockClass->side = $block->side;
                    $BlockClass->sideName = self::getSideLetters($block->side);
                    $BlockClass->templateName = "block_" . $BlockClass->sideName;
                    $BlockClass->title = $block->title;
                    $BlockClass->content = $block->content;
                    $BlockClass->rank = $block->rank;

                    // Capture des données d'affichage
                    ob_start();
                    $BlockClass->display();
                    $this->blocksCompiled[$block->side][] = ob_get_contents();
                    ob_end_clean();
                } else {
                    Core_Logger::addErrorMessage(ERROR_BLOCK_CODE);
                }
            }
        }
    }

    /**
     * Retourne le type d'orientation/postion en lettres.
     *
     * @param int $side
     * @return string identifiant de la position (right, left...).
     */
    private static function &getSideLetters($side) {
        $sideLetters = $side; // Assignation par défaut
        // Si on renseigne bien un entier
        if (is_numeric($side)) { // On recherche la position
            switch ($side) {
                case 1:
                    $sideLetters = "right";
                    break;
                case 2:
                    $sideLetters = "left";
                    break;
                case 3:
                    $sideLetters = "top";
                    break;
                case 4:
                    $sideLetters = "bottom";
                    break;
                case 5:
                    $sideLetters = "moduletop";
                    break;
                case 6:
                    $sideLetters = "modulebottom";
                    break;
                default :
                    Core_Secure::getInstance()->throwException("blockSide", "Numeric side:" . $side);
            }
        }
        return $sideLetters;
    }

    /**
     * Retourne le type d'orientation/position en chiffre.
     *
     * @param string $side
     * @return int identifiant de la position (1, 2..).
     */
    private static function &getSideNumeric($side) {
        $sideNumeric = $side; // Assignation par défaut
        // Si on renseigne bien un string
        if (is_string($side)) { // On recherche la position
            switch ($side) {
                case 'right':
                    $sideNumeric = 1;
                    break;
                case 'left':
                    $sideNumeric = 2;
                    break;
                case 'top':
                    $sideNumeric = 3;
                    break;
                case 'bottom':
                    $sideNumeric = 4;
                    break;
                case 'moduletop':
                    $sideNumeric = 5;
                    break;
                case 'modulebottom':
                    $sideNumeric = 6;
                    break;
                default :
                    Core_Secure::getInstance()->throwException("blockSide", "Letters side:" . $side);
            }
        }
        return $sideNumeric;
    }

    /**
     * Réécriture du tampon de sortie si besoin.
     *
     * @param string $buffer
     * @param int $side
     * @param int $key
     * @return string
     */
    private function &getBlockBuffer(&$buffer, $side, $key) {
        // Recherche le parametre indiquant qu'il doit y avoir une réécriture du buffer
        if (strpos(self::$blocksConfig[$side][$key]->content, "rewriteBuffer") !== false) {
            $buffer = Core_UrlRewriting::getInstance()->rewriteBuffer($buffer);
        }
        return $buffer;
    }

}
