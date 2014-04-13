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
    private $blocksInfo = array();

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
    public function launchAllBlock() {
        $this->launchBlock(array(
            "side > 0",
            "&& rank >= 0"), true);
    }

    /**
     * Charge un block.
     *
     * @param array $where exemple array(block_id = '0').
     */
    public function launchOneBlock(array $where = array()) {
        if (empty($where)) {
            // Capture de la variable
            $where = array(
                "block_id = '" . Core_Request::getInt("blockId") . "'");
        }

        $this->launchBlock($where, false);
    }

    /**
     * Retourne les blocks compilés via le nom de leurs positions.
     *
     * @param string $selectedSideName
     * @return string
     */
    public function &getBlocksBySideName($selectedSideName) {
        $selectedSide = self::getSideNumeric($selectedSideName);
        return $this->getBlocks($selectedSide);
    }

    /**
     * Retourne le premier block compilé.
     *
     * @return string
     */
    public function &getBlock() {
        return $this->getBlocks(-1);
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
        // Assignation par défaut
        $litteralSideName = $side;

        if (is_numeric($side)) {
            $litteralSideName = strtoupper(self::getSideLetters($side));
        }
        return defined($litteralSideName) ? constant($litteralSideName) : $litteralSideName;
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
     * Retourne les blocks compilés via leurs positions.
     *
     * @param int $selectedSide
     * @return string
     */
    private function &getBlocks($selectedSide) {
        $buffer = "";

        if ($selectedSide === -1 || ($selectedSide >= 0 && isset($this->blocksInfo[$selectedSide]))) {
            foreach ($this->blocksInfo as $side => $blockInfoInSide) {
                if ($selectedSide >= 0 && $side !== $selectedSide) {
                    continue;
                }

                foreach ($blockInfoInSide as $blockInfo) {
                    $currentBuffer = $blockInfo->getBuffer();

                    // Recherche le parametre indiquant qu'il doit y avoir une réécriture du buffer
                    if (strpos($blockInfo->getContent(), "rewriteBuffer") !== false) {
                        $currentBuffer = Core_UrlRewriting::getInstance()->rewriteBuffer($currentBuffer);
                    }

                    $buffer .= $currentBuffer;

                    if ($selectedSide === -1) {
                        break 2;
                    }
                }
            }
        }
        return $buffer;
    }

    /**
     * Chargement de blocks.
     *
     * @param array $where
     * @param boolean $checkModule
     */
    private function launchBlock(array $where, $checkModule) {
        // TODO mettre en cache la requete
        Core_Sql::getInstance()->select(
        Core_Table::$BLOCKS_TABLE, array(
            "block_id",
            "side",
            "position",
            "title",
            "content",
            "type",
            "rank",
            "mods"), $where, array(
            "side",
            "position")
        );

        if (Core_Sql::getInstance()->affectedRows() > 0) {
            // Récuperation des données des blocks
            Core_Sql::getInstance()->addBuffer("block");

            foreach (Core_Sql::getInstance()->fetchBuffer("block") as $block) {
                $blockInfo = new Libs_BlockData($block);
                $blockInfo->setSideName(self::getSideLetters($blockInfo->getSide()));

                if ($blockInfo->isValid() && $blockInfo->canActive($checkModule)) {
                    $this->blocksInfo[$blockInfo->getSide()][] = $blockInfo;
                    $this->get($blockInfo);
                }
            }
        }
    }

    /**
     * Récupère le block.
     *
     * @param Libs_BlockData $blockInfo
     */
    private function get(&$blockInfo) {
        // Vérification de l'accès
        if (Core_Access::autorize("block" . $blockInfo->getId(), $blockInfo->getRank())) {
            $blockClassName = "Block_" . ucfirst($blockInfo->getType());
            $loaded = Core_Loader::classLoader($blockClassName);

            // Vérification du block
            if ($loaded) {
                if (Core_Loader::isCallable($blockClassName, "display")) {
                    Core_Translate::getInstance()->translate("blocks/" . $blockInfo->getType());

                    $blockClass = new $blockClassName();
                    $blockClass->setBlockData($blockInfo);

                    // Capture des données d'affichage
                    ob_start();
                    $blockClass->display();
                    $blockInfo->setBuffer(ob_get_contents());
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
    public static function &getSideLetters($side) {
        if (!is_numeric($side)) {
            Core_Secure::getInstance()->throwException("blockSide", "Invalid side value: " . $side);
        }

        $sideLetters = "";

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
                Core_Secure::getInstance()->throwException("blockSide", "Numeric side: " . $side);
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
        if (!is_string($side)) {
            ore_Secure::getInstance()->throwException("blockSide", "Invalid side value: " . $side);
        }

        $sideNumeric = 0;

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
                Core_Secure::getInstance()->throwException("blockSide", "Letters side: " . $side);
        }
        return $sideNumeric;
    }

}
