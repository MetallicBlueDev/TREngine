<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreAccess;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreLogger;
use TREngine\Engine\Core\CoreRequest;
use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Core\CoreTranslate;
use TREngine\Engine\Core\CoreUrlRewriting;
use Exception;

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
     * Demande le premier block compilé (toute position confondu).
     *
     * @var int
     */
    const SIDE_FIRST_BLOCK = -1;

    /**
     * Postion inconnue (cas de désactivation).
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
        "modulebottom" => self::SIDE_MODULE_BOTTOM
    );

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
    private $blockInfos = array();

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
    public static function &getInstance(): LibBlock {
        if (self::$libBlock === null) {
            self::$libBlock = new LibBlock();
        }
        return self::$libBlock;
    }

    /**
     * Ajout d'un type de block qui peut être appelé en standalone.
     *
     * @param string $blockTypeName
     */
    public function addStandaloneBlockType(string $blockTypeName) {
        $this->standaloneBlocksType[] = $blockTypeName;
    }

    /**
     * Détermine si le block est utilisable en standalone.
     *
     * @param string $blockTypeName
     * @return bool
     */
    public function isStandaloneBlockType(string $blockTypeName): bool {
        return !empty($blockTypeName) && (array_search($blockTypeName, $this->standaloneBlocksType) !== false);
    }

    /**
     * Démarrage des blocks.
     */
    public function launchAllBlock() {
        // Recherche dans l'indexeur
        $blocksIndexer = $this->getBlocksIndexer();

        foreach ($blocksIndexer as $blockRawInfo) {
            if ($blockRawInfo['side'] > self::SIDE_NONE && $blockRawInfo['rank'] >= CoreAccess::RANK_NONE) {
                $this->launchBlockById($blockRawInfo['block_id'], true);
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
            $this->launchStandaloneBlockType($blockType);
        }
    }

    /**
     * Démarrage d'un block via son type.
     *
     * @param string $blockTypeName
     */
    public function launchStandaloneBlockType(string $blockTypeName) {
        if (!$this->isStandaloneBlockType($blockTypeName)) {
            CoreSecure::getInstance()->throwException("blockType", null, array(
                "Invalid type value: " . $blockTypeName
            ));
        }

        $empty = array(
            "block_id" => 1,
            "type" => $blockTypeName,
            "side" => self::SIDE_RIGHT,
            "mods" => "all",
            "title" => $blockTypeName
        );
        $blockInfo = new LibBlockData($empty);
        $this->addBlockInfo($blockInfo);
        $this->launchBlock($blockInfo, false);
    }

    /**
     * Retourne les blocks compilés via le nom de leurs positions.
     *
     * @param string $sideName
     * @return string
     */
    public function &getBlocksBySideName(string $sideName): string {
        $sideNumeric = self::getSideAsNumeric($sideName);
        return $this->getBlocksBySidePosition($sideNumeric);
    }

    /**
     * Retourne le premier block compilé.
     *
     * @return string
     */
    public function &getBlock(): string {
        return $this->getBlocksBySidePosition(self::SIDE_FIRST_BLOCK);
    }

    /**
     * Liste des orientations possible.
     *
     * @return array array("numeric" => identifiant int, "letters" => nom de la position).
     */
    public static function &getSideList(): array {
        $sideList = array();

        foreach (self::SIDE_LIST as $sideNumeric) {
            $sideList[] = array(
                "numeric" => $sideNumeric,
                "letters" => self::getSideAsLitteral($sideNumeric)
            );
        }
        return $sideList;
    }

    /**
     * Retourne le type d'orientation avec la traduction.
     *
     * @param mixed $side
     * @return string postion traduit (si possible).
     */
    public static function &getSideAsLitteral($side): string {
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
    public static function &getSideAsLetters(int $side): string {
        $sideLetters = array_search($side, self::SIDE_LIST);

        if ($sideLetters === false) {
            CoreSecure::getInstance()->throwException("blockSide", null, array(
                "Numeric side: " . $side
            ));
        }
        return $sideLetters;
    }

    /**
     * Retourne la liste des blocks disponibles.
     *
     * @return array
     */
    public static function &getBlockList(): array {
        return CoreCache::getInstance()->getFileList("Blocks", "Block");
    }

    /**
     * Retourne les informations du block cible.
     *
     * @param int $blockId l'identifiant du block.
     * @return LibBlockData Informations sur le block.
     */
    public function &getBlockInfo(int $blockId): LibBlockData {
        $blockInfo = null;

        if (isset($this->blockInfos[$blockId])) {
            $blockInfo = $this->blockInfos[$blockId];
        } else {
            $blockData = $this->createBlockInfo($blockId);

            // Injection des informations du block
            $blockInfo = new LibBlockData($blockData);
            $this->addBlockInfo($blockInfo);
        }
        return $blockInfo;
    }

    /**
     * Création des informations sur le block.
     *
     * @param int $blockId
     * @return array
     */
    private function &createBlockInfo(int $blockId): array {
        $blockData = array();

        // Recherche dans le cache
        $coreCache = CoreCache::getInstance(CoreCache::SECTION_BLOCKS);

        if (!$coreCache->cached($blockId . ".php")) {
            $coreSql = CoreSql::getInstance();

            $coreSql->select(CoreTable::BLOCKS_TABLE, array(
                "block_id",
                "side",
                "position",
                "title",
                "content",
                "type",
                "rank",
                "mods"
            ), array(
                "block_id =  '" . $blockId . "'"
            ));

            if ($coreSql->affectedRows() > 0) {
                $blockData = $coreSql->fetchArray()[0];

                // Mise en cache
                $content = $coreCache->serializeData($blockData);
                $coreCache->writeCache($blockId . ".php", $content);
            }
        } else {
            $blockData = $coreCache->readCache($blockId . ".php");
        }
        return $blockData;
    }

    /**
     * Alimente le cache des blocks.
     *
     * @param LibBlockData $blockInfo
     */
    private function addBlockInfo(LibBlockData $blockInfo) {
        $this->blockInfos[$blockInfo->getId()] = $blockInfo;
    }

    /**
     * Retourne un tableau définissant les blocks disponibles.
     *
     * @return array
     */
    private function getBlocksIndexer(): array {
        $blocksIndexer = array();
        $coreCache = CoreCache::getInstance(CoreCache::SECTION_BLOCKS);

        if (!$coreCache->cached(self::BLOCKS_INDEXER_FILENAME)) {
            $coreSql = CoreSql::getInstance();

            $coreSql->select(CoreTable::BLOCKS_TABLE, array(
                "block_id",
                "side",
                "type",
                "rank"
            ), array(), array(
                "side",
                "position"
            ));

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
    private function &getBlocksBySidePosition(int $selectedSide): string {
        $buffer = "";

        if ($selectedSide === self::SIDE_FIRST_BLOCK || $selectedSide >= self::SIDE_NONE) {
            foreach ($this->blockInfos as $blockInfo) {
                if ($selectedSide >= self::SIDE_NONE && $blockInfo->getSide() !== $selectedSide) {
                    continue;
                }

                $currentBuffer = $blockInfo->getBuffer();

                // Recherche le parametre indiquant qu'il doit y avoir une réécriture du buffer
                if (strpos($blockInfo->getContent(), "rewriteBuffer") !== false) {
                    $currentBuffer = CoreUrlRewriting::getInstance()->rewriteBuffer($currentBuffer);
                }

                $buffer .= $currentBuffer;

                if ($selectedSide === self::SIDE_FIRST_BLOCK) {
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
    private function launchBlockById(int $blockId, bool $checkModule) {
        $blockInfo = $this->getBlockInfo($blockId);
        $this->launchBlock($blockInfo, $checkModule);
    }

    /**
     * Démarrage d'un block.
     *
     * @param LibBlockData $blockInfo
     * @param bool $checkModule
     */
    private function launchBlock(LibBlockData $blockInfo, bool $checkModule) {
        if ($blockInfo->isValid() && $blockInfo->canActive($checkModule)) {
            $this->build($blockInfo);
        }
    }

    /**
     * Compile le block.
     *
     * @param LibBlockData $blockInfo
     */
    private function build(LibBlockData &$blockInfo) {
        $blockClassName = CoreLoader::getFullQualifiedClassName($blockInfo->getClassName(), $blockInfo->getFolderName());
        $loaded = CoreLoader::classLoader($blockClassName);

        // Vérification du block
        if ($loaded && CoreLoader::isCallable($blockClassName, "display")) {
            CoreTranslate::getInstance()->translate($blockInfo->getFolderName());

            try {
                /**
                 *
                 * @var BlockModel
                 */
                $blockClass = new $blockClassName();
                $blockClass->setBlockData($blockInfo);

                // Capture des données d'affichage
                ob_start();
                $blockClass->display();
                $blockInfo->setBuffer(ob_get_clean());
            } catch (Exception $ex) {
                CoreSecure::getInstance()->throwException($ex->getMessage(), $ex);
            }
        } else {
            CoreLogger::addErrorMessage(ERROR_BLOCK_CODE . " (" . $blockInfo->getType() . ")");
        }
    }

    /**
     * Retourne le type d'orientation/position en chiffre.
     *
     * @param string $sideName
     * @return int identifiant de la position (1, 2..).
     */
    private static function &getSideAsNumeric(string $sideName): int {
        if (!isset(self::SIDE_LIST[$sideName])) {
            CoreSecure::getInstance()->throwException("blockSide", null, array(
                "Letters side: " . $sideName
            ));
        }

        $sideNumeric = self::SIDE_LIST[$sideName];
        return $sideNumeric;
    }

}
