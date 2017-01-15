<?php

namespace TREngine\Engine\Lib;

use Exception;
use TREngine\Engine\Block\BlockModel;
use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreTranslate;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreAccess;
use TREngine\Engine\Core\CoreUrlRewriting;
use TREngine\Engine\Core\CoreRequest;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de blocks.
 *
 * @author Sébastien Villemain
 */
class LibBlock {

    /**
     * Nom du fichier cache de bannissement.
     *
     * @var string
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
    const SIDE_LIST = array(
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
     * @var LibBlock
     */
    private static $libBlock = null;

    /**
     * Blocks chargés.
     *
     * @var LibBlockData[]
     */
    private $blocksInfo = array();

    /**
     * Liste des blocks qui peuvent être appelés en standalone.
     *
     * @var array
     */
    private $standaloneBlocksType = array();

    private function __construct() {
        $this->addStandaloneBlockType("Login");
        $this->addStandaloneBlockType("ImageGenerator");
    }

    /**
     * Instance du gestionnaire de block.
     *
     * @return LibBlock
     */
    public static function &getInstance() {
        if (self::$libBlock === null) {
            self::$libBlock = new LibBlock();
        }
        return self::$libBlock;
    }

    /**
     * Ajout d'un type de block qui peut être appelé en standalone.
     *
     * @param string $blockType
     */
    public function addStandaloneBlockType($blockType) {
        $this->standaloneBlocksType[] = $blockType;
    }

    /**
     * Détermine si le block est utilisable en standalone.
     *
     * @param string $blockType
     * @return bool
     */
    public function isStandaloneBlockType($blockType) {
        return (array_search($blockType, $this->standaloneBlocksType) !== false);
    }

    /**
     * Démarrage des blocks.
     */
    public function launchAllBlock() {
        // Recherche dans l'indexeur
        $blocksIndexer = $this->getBlocksIndexer();

        foreach ($blocksIndexer as $blockRawInfo) {
            if ($blockRawInfo['side'] > self::SIDE_NONE) {
                if ($blockRawInfo['rank'] >= CoreAccess::RANK_NONE) {
                    $this->launchBlockById($blockRawInfo['block_id'], true);
                }
            }
        }
    }

    /**
     * Démarrage du block demandé depuis une requête.
     */
    public function launchBlockRequested() {
        $blockId = CoreRequest::getInteger("blockId", -1);

        if ($blockId >= 0) {
            $this->launchBlockById($blockId, false);
        } else {
            $blockType = CoreRequest::getString("blockType");
            $this->launchBlockByType($blockType);
        }
    }

    /**
     * Démarrage d'un block via son type.
     */
    public function launchBlockByType($blockType) {
        if (empty($blockType) || !$this->isStandaloneBlockType($blockType)) {
            CoreSecure::getInstance()->throwException("blockType", null, array(
                "Invalid type value: " . $blockType));
        }

        $empty = array(
            "block_id" => 1,
            "type" => $blockType,
            "side" => self::SIDE_RIGHT,
            "mods" => "all",
            "title" => $blockType);
        $blockInfo = new LibBlockData($empty);
        $this->setBlocksInfo($blockInfo);
        $this->launchBlock($blockInfo, false);
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
     * Retourne le type d'orientation/postion en lettres.
     *
     * @param int $side
     * @return string identifiant de la position (right, left...).
     */
    public static function &getSideAsLetters($side) {
        if (!is_numeric($side)) {
            CoreSecure::getInstance()->throwException("blockSide", null, array(
                "Invalid side value: " . $side));
        }

        $sideLetters = array_search($side, self::SIDE_LIST);

        if ($sideLetters === false) {
            CoreSecure::getInstance()->throwException("blockSide", null, array(
                "Numeric side: " . $side));
        }
        return $sideLetters;
    }

    /**
     * Retourne la liste des blocks disponibles.
     *
     * @return array
     */
    public static function &getBlockList() {
        return CoreCache::getInstance()->getFileList("Blocks", "Block");
    }

    /**
     * Retourne les informations du block cible.
     *
     * @param int $blockId l'identifiant du block.
     * @return LibBlockData Informations sur le block.
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
            $blockInfo = new LibBlockData($blockData);
            $this->setBlocksInfo($blockInfo);
        }
        return $blockInfo;
    }

    /**
     * Alimente le cache des blocks.
     *
     * @param LibBlockData $blockInfo
     */
    private function setBlocksInfo(LibBlockData $blockInfo) {
        $this->blocksInfo[$blockInfo->getId()] = $blockInfo;
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
     * Démarrage d'un block via son identifiant.
     *
     * @param int $blockId
     * @param bool $checkModule
     */
    private function launchBlockById($blockId, $checkModule) {
        $blockInfo = $this->getInfoBlock($blockId);
        $this->launchBlock($blockInfo, $checkModule);
    }

    /**
     * Démarrage d'un block.
     *
     * @param LibBlockData $blockInfo
     * @param bool $checkModule
     */
    private function launchBlock(LibBlockData $blockInfo, $checkModule) {
        if ($blockInfo->isValid()) {
            if ($blockInfo->canActive($checkModule)) {
                $this->get($blockInfo);
            }
        }
    }

    /**
     * Récupère le block.
     *
     * @param LibBlockData $blockInfo
     */
    private function get(&$blockInfo) {
        $blockClassName = CoreLoader::getFullQualifiedClassName($blockInfo->getClassName(), $blockInfo->getFolderName());
        $loaded = CoreLoader::classLoader($blockClassName);

        // Vérification du block
        if ($loaded && CoreLoader::isCallable($blockClassName, "display")) {
            CoreTranslate::getInstance()->translate($blockInfo->getFolderName());

            try {
                /**
                 * @var BlockModel
                 */
                $blockClass = new $blockClassName();
                $blockClass->setBlockData($blockInfo);

                // Capture des données d'affichage
                ob_start();
                $blockClass->display();
                $blockInfo->setBuffer(ob_get_clean());
            } catch (Exception $ex) {
                // PHP 7
                CoreSecure::getInstance()->throwException($ex->getMessage(), $ex);
            }
        } else {
            CoreLogger::addErrorMessage(ERROR_BLOCK_CODE . " (" . $blockInfo->getType() . ")");
        }
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

        if (!isset(self::SIDE_LIST[$side])) {
            CoreSecure::getInstance()->throwException("blockSide", null, array(
                "Letters side: " . $side));
        }

        $sideNumeric = self::SIDE_LIST[$side];
        return $sideNumeric;
    }

}
