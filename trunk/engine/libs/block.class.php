<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Gestionnaire de blocks.
 *
 * @author Sébastien Villemain
 */
class Libs_Block {

    /**
     * Postion inconnue.
     *
     * @var int
     */
    const SIDE_NONE = 0;

    /**
     * Postion le plus à droite.
     *
     * @var int
     */
    const SIDE_RIGHT = 1;

    /**
     * Position le plus à gauche.
     *
     * @var int
     */
    const SIDE_LEFT = 2;

    /**
     * Position le plus en haut.
     *
     * @var int
     */
    const SIDE_TOP = 3;

    /**
     * Position le plus en bas.
     *
     * @var int
     */
    const SIDE_BOTTOM = 4;

    /**
     * Position dans le module à droite.
     *
     * @var int
     */
    const SIDE_MODULE_RIGHT = 5;

    /**
     * Position dans le module à gauche.
     *
     * @var int
     */
    const SIDE_MODULE_LEFT = 6;

    /**
     * Position dans le module en haut.
     *
     * @var int
     */
    const SIDE_MODULE_TOP = 7;

    /**
     * Position dans le module en bas.
     *
     * @var int
     */
    const SIDE_MODULE_BOTTOM = 8;

    /**
     * Liste des positions valides.
     *
     * @var array array("name" => 0)
     */
    private static $sideRegistred = array(
        "right" => self::SIDE_RIGHT,
        "left" => self::SIDE_LEFT,
        "top" => self::SIDE_TOP,
        "bottom" => self::SIDE_BOTTOM,
        "moduleright" => self::SIDE_MODULE_RIGHT,
        "moduleleft" => self::SIDE_MODULE_LEFT,
        "moduletop" => self::SIDE_MODULE_TOP,
        "modulebottom" => self::SIDE_MODULE_BOTTOM);

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
        if (self::$libsBlock === null) {
            self::$libsBlock = new Libs_Block();
        }
        return self::$libsBlock;
    }

    /**
     * Charge les blocks.
     */
    public function launchAllBlock() {
        $this->launchBlock(array(
            "side > " . self::SIDE_NONE,
            "&& rank >= " . Core_Access::RANK_NONE), true);
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
                "block_id = '" . Core_Request::getInteger("blockId") . "'");
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
        $selectedSide = self::getSideAsNumeric($selectedSideName);
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
    public static function &getSideList() {
        $sideList = array();

        for ($i = 1; $i < 7; $i++) {
            $sideList[] = array(
                "numeric" => $i,
                "letters" => self::getSideAsLitteral($i));
        }
        return $sideList;
    }

    /**
     * Retourne le type d'orientation avec la traduction.
     *
     * @param string $side
     * @return string postion traduit (si possible).
     */
    public static function &getSideAsLitteral($side) {
        // Assignation par défaut
        $litteralSideName = $side;

        if (is_numeric($side)) {
            $litteralSideName = strtoupper(self::getSideAsLetters($side));
        }

        $litteralSideName = defined($litteralSideName) ? constant($litteralSideName) : $litteralSideName;
        return $litteralSideName;
    }

    /**
     * Retourne la liste des blocks disponibles.
     *
     * @return array
     */
    public static function &getBlockList() {
        return Core_Cache::getInstance()->getFileList("blocks", ".block");
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
     * Retourne les informations des blocks cibles.
     *
     * @param array $where
     * @return Libs_BlockData Informations sur les blocks.
     */
    private function &getInfoBlocks(array $where) {
        $blockInfos = array();

        // TODO mettre en cache la requete
        $coreSql = Core_Sql::getInstance();
        $coreSql->select(
        Core_Table::BLOCKS_TABLE, array(
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

        if ($coreSql->affectedRows() > 0) {
            // Récuperation des données des blocks
            $coreSql->addArrayBuffer("block");

            foreach ($coreSql->fetchBuffer("block") as $block) {
                $blockInfo = new Libs_BlockData($block);
                $blockInfo->setSideName(self::getSideAsLetters($blockInfo->getSide()));

                $blockInfos[] = $blockInfo;
                $this->blocksInfo[$blockInfo->getSide()][] = $blockInfo;
            }
        }
        return $blockInfos;
    }

    /**
     * Chargement de blocks.
     *
     * @param array $where
     * @param boolean $checkModule
     */
    private function launchBlock(array $where, $checkModule) {
        foreach ($this->getInfoBlocks($where) as $blockInfo) {
            if ($blockInfo->isValid() && $blockInfo->canActive($checkModule)) {
                $this->get($blockInfo);
            }
        }
    }

    /**
     * Récupère le block.
     *
     * @param Libs_BlockData $blockInfo
     */
    private function get(&$blockInfo) {
        $blockClassName = "Block_" . ucfirst($blockInfo->getType());
        $loaded = Core_Loader::classLoader($blockClassName);

        // Vérification du block
        if ($loaded) {
            if (Core_Loader::isCallable($blockClassName, "display")) {
                Core_Translate::getInstance()->translate("blocks/" . $blockInfo->getType());

                /**
                 * @var Block_Model
                 */
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

    /**
     * Retourne le type d'orientation/postion en lettres.
     *
     * @param int $side
     * @return string identifiant de la position (right, left...).
     */
    public static function &getSideAsLetters($side) {
        if (!is_numeric($side)) {
            Core_Secure::getInstance()->throwException("blockSide", null, array(
                "Invalid side value: " . $side));
        }

        $sideLetters = array_search($side, self::$sideRegistred, true);

        if ($sideLetters === false) {
            Core_Secure::getInstance()->throwException("blockSide", null, array(
                "Numeric side: " . $side));
        }
        return $sideLetters;
    }

    /**
     * Retourne le type d'orientation/position en chiffre.
     *
     * @param string $side
     * @return int identifiant de la position (1, 2..).
     */
    private static function &getSideAsNumeric($side) {
        if (!is_string($side)) {
            Core_Secure::getInstance()->throwException("blockSide", null, array(
                "Invalid side value: " . $side));
        }

        if (!isset(self::$sideRegistred[$side])) {
            Core_Secure::getInstance()->throwException("blockSide", null, array(
                "Letters side: " . $side));
        }

        $sideNumeric = self::$sideRegistred[$side];
        return $sideNumeric;
    }

}
