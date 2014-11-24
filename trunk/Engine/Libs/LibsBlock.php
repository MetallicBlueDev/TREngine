<?php

namespace TREngine\Engine\Libs;

use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreTranslate;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de blocks.
 *
 * @author Sébastien Villemain
 */
class LibsBlock {

    /**
     * Nom du fichier cache de bannissement.
     */
    const BLOCKS_INDEXER_FILENAME = "blocks_indexer.php";

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
     * @var LibsBlock
     */
    private static $libsBlock = null;

    /**
     * Blocks chargés.
     *
     * @var LibsBlockData[]
     */
    private $blocksInfo = array();

    private function __construct() {

    }

    /**
     * Instance du gestionnaire de block.
     *
     * @return LibsBlock
     */
    public static function &getInstance() {
        if (self::$libsBlock === null) {
            self::$libsBlock = new LibsBlock();
        }
        return self::$libsBlock;
    }

    /**
     * Charge les blocks.
     */
    public function launchAllBlock() {
        // Recherche dans l'indexeur
        $blocksIndexer = $this->getBlocksIndexer();

        foreach ($blocksIndexer as $blockRawInfo) {
            if ($blockRawInfo['side'] > self::SIDE_NONE) {
                if ($blockRawInfo['rank'] >= CoreAccess::RANK_NONE) {
                    $this->launchBlock($blockRawInfo['block_id'], true);
                }
            }
        }
    }

    /**
     * Charge le block requêté.
     */
    public function launchBlockRequested() {
        $blockId = CoreRequest::getInteger("blockId");
        $this->launchBlock($blockId, false);
    }

    /**
     * Charge un block par son type.
     *
     * @param string $type
     */
    public function launchBlockType($type) {
        $blockId = -1;

        // Recherche dans le cache local
        foreach ($this->blocksInfo as $blockRawInfo) {
            if ($blockRawInfo->getType() === $type) {
                $blockId = $blockRawInfo->getId();
                break;
            }
        }

        if ($blockId === -1) {
            // Recherche dans l'indexeur
            $blocksIndexer = $this->getBlocksIndexer();

            foreach ($blocksIndexer as $blockRawInfo) {
                if ($blockRawInfo['type'] === $type) {
                    $blockId = $blockRawInfo['block_id'];
                    break;
                }
            }
        }

        if ($blockId >= 0) {
            $this->launchBlock($blockId, false);
        }
    }

    /**
     * Retourne les blocks compilés via le nom de leurs positions.
     *
     * @param string $selectedSideName
     * @return string
     */
    public function &getBlocksBySideName($selectedSideName) {
        $selectedSide = self::getSideAsNumeric($selectedSideName);
        return $this->getBlocksBySidePosition($selectedSide);
    }

    /**
     * Retourne le premier block compilé.
     *
     * @return string
     */
    public function &getBlock() {
        return $this->getBlocksBySidePosition(-1);
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
        return CoreCache::getInstance()->getFileList("blocks", ".block");
    }

    /**
     * Retourne les informations du block cible.
     *
     * @param int $blockId l'identifiant du block.
     * @return LibsBlockData Informations sur le block.
     */
    public function &getInfoBlock($blockId) {
        $blockInfo = null;

        if (isset($this->blocksInfo[$blockId])) {
            $blockInfo = $this->blocksInfo[$blockId];
        } else {
            $blockData = array();

            // Recherche dans le cache
            $coreCache = CoreCache::getInstance(CoreCache::SECTION_BLOCKS);

            if (!$coreCache->cached($blockId . ".php")) {
                $coreSql = CoreSql::getInstance();

                $coreSql->select(
                CoreTable::BLOCKS_TABLE, array(
                    "block_id",
                    "side",
                    "position",
                    "title",
                    "content",
                    "type",
                    "rank",
                    "mods"), array(
                    "block_id =  '" . $blockId . "'")
                );

                if ($coreSql->affectedRows() > 0) {
                    $blockData = $coreSql->fetchArray()[0];

                    // Mise en cache
                    $content = $coreCache->serializeData($blockData);
                    $coreCache->writeCache($blockId . ".php", $content);
                }
            } else {
                $blockData = $coreCache->readCache($blockId . ".php");
            }

            // Injection des informations du block
            $blockInfo = new LibsBlockData($blockData);
            $blockInfo->setSideName(self::getSideAsLetters($blockInfo->getSide()));
            $this->blocksInfo[$blockId] = $blockInfo;
        }
        return $blockInfo;
    }

    /**
     * Retourne un tableau définissant les blocks disponibles.
     *
     * @return array
     */
    private function getBlocksIndexer() {
        $blocksIndexer = array();
        $coreCache = CoreCache::getInstance(CoreCache::SECTION_BLOCKS);

        if (!$coreCache->cached(self::BLOCKS_INDEXER_FILENAME)) {
            $coreSql = CoreSql::getInstance();

            $coreSql->select(
            CoreTable::BLOCKS_TABLE, array(
                "block_id",
                "side",
                "type",
                "rank"), array(), array(
                "side",
                "position")
            );

            if ($coreSql->affectedRows() > 0) {
                $blocksIndexer = $coreSql->fetchArray();

                // Mise en cache
                $content = $coreCache->serializeData($blocksIndexer);
                $coreCache->writeCache("blocks_indexer.php", $content);
            }
        } else {
            $blocksIndexer = $coreCache->readCache(self::BLOCKS_INDEXER_FILENAME);
        }
        return $blocksIndexer;
    }

    /**
     * Retourne les blocks compilés via leurs positions.
     *
     * @param int $selectedSide
     * @return string
     */
    private function &getBlocksBySidePosition($selectedSide) {
        $buffer = "";

        if ($selectedSide === -1 || $selectedSide >= 0) {
            foreach ($this->blocksInfo as $blockInfo) {
                if ($selectedSide >= 0 && $blockInfo->getSide() !== $selectedSide) {
                    continue;
                }

                $currentBuffer = $blockInfo->getBuffer();

                // Recherche le parametre indiquant qu'il doit y avoir une réécriture du buffer
                if (strpos($blockInfo->getContent(), "rewriteBuffer") !== false) {
                    $currentBuffer = CoreUrlRewriting::getInstance()->rewriteBuffer($currentBuffer);
                }

                $buffer .= $currentBuffer;

                if ($selectedSide === -1) {
                    break;
                }
            }
        }
        return $buffer;
    }

    /**
     * Chargement d'un block.
     *
     * @param int $blockId
     * @param boolean $checkModule
     */
    private function launchBlock($blockId, $checkModule) {
        $blockInfo = $this->getInfoBlock($blockId);

        if ($blockInfo->isValid()) {
            if ($blockInfo->canActive($checkModule)) {
                $this->get($blockInfo);
            }
        }
    }

    /**
     * Récupère le block.
     *
     * @param LibsBlockData $blockInfo
     */
    private function get(&$blockInfo) {
        $blockClassName = "Block" . ucfirst($blockInfo->getType());
        $loaded = CoreLoader::classLoader($blockClassName);

        // Vérification du block
        if ($loaded) {
            if (CoreLoader::isCallable($blockClassName, "display")) {
                CoreTranslate::getInstance()->translate("blocks" . DIRECTORY_SEPARATOR . $blockInfo->getType());

                /**
                 * @var BlockModel
                 */
                $blockClass = new $blockClassName();
                $blockClass->setBlockData($blockInfo);

                // Capture des données d'affichage
                ob_start();
                $blockClass->display();
                $blockInfo->setBuffer(ob_get_contents());
                ob_end_clean();
            } else {
                CoreLogger::addErrorMessage(ERROR_BLOCK_CODE);
            }
        }
    }

    /**
     * Retourne le type d'orientation/postion en lettres.
     *
     * @param int $side
     * @return string identifiant de la position (right, left...).
     */
    private static function &getSideAsLetters($side) {
        if (!is_numeric($side)) {
            CoreSecure::getInstance()->throwException("blockSide", null, array(
                "Invalid side value: " . $side));
        }

        $sideLetters = array_search($side, self::$sideRegistred);

        if ($sideLetters === false) {
            CoreSecure::getInstance()->throwException("blockSide", null, array(
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
            CoreSecure::getInstance()->throwException("blockSide", null, array(
                "Invalid side value: " . $side));
        }

        if (!isset(self::$sideRegistred[$side])) {
            CoreSecure::getInstance()->throwException("blockSide", null, array(
                "Letters side: " . $side));
        }

        $sideNumeric = self::$sideRegistred[$side];
        return $sideNumeric;
    }

}
