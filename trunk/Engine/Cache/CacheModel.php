<?php

namespace TREngine\Engine\Cache;

use TREngine\Engine\Core\CoreTransaction;
use TREngine\Engine\Fail\FailCache;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Modèle pour un gestionnaire de fichier.
 *
 * @author Sébastien Villemain
 */
abstract class CacheModel extends CoreTransaction {

    /**
     * Droit d'écriture CHMOD.
     * Sous forme de 4 octets.
     * Exemple : 0777.
     *
     * @var int
     */
    protected $chmod = 0777;

    /**
     * {@inheritDoc}
     * @param string $message
     * @throws FailCache
     */
    protected function throwException(string $message) {
        throw new FailCache("cache" . $message);
    }

    /**
     * {@inheritDoc}
     * @param array $transaction
     * @throws FailCache
     */
    public function initialize(array &$transaction) {
        if (!empty($transaction)) {
            if (preg_match("/(ftp:\/\/)(.+)/", $transaction['host'], $matches)) {
                $transaction['host'] = $matches[2];
            }

            if (preg_match("/(.+)(\/)/", $transaction['host'], $matches)) {
                $transaction['host'] = $matches[1];
            }

            // Réglage de configuration
            $transaction['host'] = (empty($transaction['host'])) ? "127.0.0.1" : $transaction['host'];
            $transaction['port'] = (is_numeric($transaction['port'])) ? $transaction['port'] : 21;
            $transaction['user'] = (empty($transaction['user'])) ? "root" : $transaction['user'];
            $transaction['pass'] = (empty($transaction['pass'])) ? "" : $transaction['pass'];

            // Le dossier root sera redéfini après être identifié
            $transaction['root'] = (empty($transaction['root'])) ? DIRECTORY_SEPARATOR : $transaction['root'];
        }

        parent::initialize($transaction);
    }

    /**
     * Retourne le port.
     *
     * @return int
     */
    public function &getServerPort(): int {
        return $this->getIntValue("port");
    }

    /**
     * Retourne le chemin racine.
     *
     * @return string
     */
    public function &getServerRoot(): string {
        return $this->getStringValue("root");
    }

    /**
     * Affecte le chemin racine.
     *
     * @param string $newRoot
     */
    public function setServerRoot(string &$newRoot) {
        $this->setDataValue("root", $newRoot);
    }

    /**
     * Ecriture du fichier cache.
     *
     * @param string $path chemin vers le fichier cache
     * @param mixed $content contenu du fichier cache
     * @param bool $overwrite écrasement du fichier
     */
    public function writeCache(string $path, $content, bool $overwrite = true) {
        unset($path);
        unset($content);
        unset($overwrite);
    }

    /**
     * Mise à jour de la date de dernière modification.
     *
     * @param string $path chemin vers le fichier cache
     * @param int $updateTime
     */
    public function touchCache(string $path, int $updateTime = 0) {
        unset($path);
        unset($updateTime);
    }

    /**
     * Supprime tous fichiers trop vieux.
     *
     * @param string $path chemin vers le fichier ou le dossier
     * @param int $timeLimit limite de temps
     */
    public function removeCache(string $path, int $timeLimit = 0) {
        unset($path);
        unset($timeLimit);
    }

    /**
     * Retourne la liste des fichiers et dossiers présents.
     *
     * @param string $path
     * @return array
     */
    public function &getNameList(string $path): array {
        unset($path);
        $names = array();
        return $names;
    }

    /**
     * Retourne la date de dernière modification du fichier.
     *
     * @param string $path
     * @return int
     */
    public function &getCacheMTime(string $path): int {
        unset($path);
        $time = 0;
        return $time;
    }

    /**
     * Détermine si le chemin est celui d'un dossier.
     *
     * @param string $path
     * @return bool true c'est un dossier
     */
    protected static function &isDirectoryPath(string $path): bool {
        $pathIsDir = false;

        if (substr($path, -1) === DIRECTORY_SEPARATOR) {
            $pathIsDir = true;
        } else {
            // Recherche du bout du path
            $supposedFileName = "";
            $pos = strrpos(DIRECTORY_SEPARATOR, $path);

            if ($pos !== false) {
                $supposedFileName = substr($path, $pos);
            } else {
                $supposedFileName = $path;
            }

            // Si ce n'est pas un fichier (avec ext.)
            if (strpos($supposedFileName, ".") === false) {
                $pathIsDir = true;
            }
        }
        return $pathIsDir;
    }

    /**
     * Ecriture de l'entête du fichier.
     *
     * @param string $filePath
     * @param string $content
     * @return string $content
     */
    protected static function &getFileHeader(string $filePath, string $content): string {
        $ext = substr($filePath, -3);

        // Entête des fichier PHP
        if ($ext === "php") {
            // Recherche du dossier parent
            $dirBase = "";

            $localDir = str_replace(TR_ENGINE_INDEXDIR, "", $filePath);

            if ($localDir[0] === DIRECTORY_SEPARATOR) {
                $localDir = substr($localDir, 1);
            }

            $nbDir = count(explode(DIRECTORY_SEPARATOR, $localDir));

            for ($i = 1; $i < $nbDir; $i++) {
                $dirBase .= ".." . DIRECTORY_SEPARATOR;
            }

            // Ecriture de l'entête
            $content = "<?php\n"
            . "if (!defined(\"TR_ENGINE_INDEX\")){"
            . "require '" . $dirBase . "Engine" . DIRECTORY_SEPARATOR . "SecurityCheck.php';"
            . "}"
            . "// Generated on " . date('Y-m-d H:i:s') . "\n"
            . $content
            . "\n?>";
        }
        return $content;
    }

}
