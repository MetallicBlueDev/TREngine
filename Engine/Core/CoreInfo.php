<?php

namespace PassionEngine\Engine\Core;

/**
 * Recherche d'information rapide sur le moteur d'exécution et son environnement coté serveur.
 *
 * <code>
 * 5.6 : 31 Dec 2018 -> INCOMPATIBLE / CoreInfo uniquement (cette classe).
 * 7.0 : 3 Dec 2018 -> INCOMPATIBLE
 * 7.1 : 1 Dec 2019 <- Version minimale
 * 7.2 : 30 Nov 2020 <- Compatible
 * 7.3 : ???
 * </code>
 *
 * @link http://php.net/supported-versions.php
 * @author Sébastien Villemain
 */
class CoreInfo
{

    /**
     * Indique si la classe a été initialisée.
     *
     * @var bool
     */
    private static $initialized = false;

    /**
     * Tableau temporaire contenant les pointeurs des variables mémorisées.
     *
     * @var array
     */
    private static $unsafeGlobalVars = array();

    private function __construct()
    {

    }

    /**
     * Mémorise les variables.
     * Utilisé pour mémoriser les contenus des Superglobales.
     *
     * Les Superglobales ne peuvent pas être appelées directement dans une classe.
     *
     * @link http://php.net/manual/language.variables.variable.php
     * @link http://php.net/manual/language.variables.superglobals.php
     * @param string $name Pointeur vers le nom de la variable.
     * @param array $value Pointeur vers le contenu de la variable.
     */
    public static function addGlobalVars(&$name,
                                         &$value)
    {
        if (!self::$initialized || ($name === '_SESSION' && !array_key_exists($name,
                                                                              self::$unsafeGlobalVars))) {
            self::$unsafeGlobalVars[$name] = $value;
        } else {
            exit('Invalid access.');
        }
    }

    /**
     * Retourne le tableau contenant les pointeurs des variables précédemment mémorisées.
     * Attention, données brutes, non sécurisées.
     *
     * @return array
     */
    public static function &getGlobalVars($name)
    {
        $input = null;

        if (isset(self::$unsafeGlobalVars[$name])) {
            $input = &self::$unsafeGlobalVars[$name];
        }

        if ($input === null) {
            $input = array();
        }
        return $input;
    }

    /**
     * Détermine si la version PHP actuelle est compatible.
     *
     * @return bool
     */
    public static function compatibleVersion()
    {
        return (PASSION_ENGINE_PHP_VERSION >= PASSION_ENGINE_PHP_MINIMUM_VERSION);
    }

    /**
     * Création des constantes contenant les informations sur l'environnement.
     */
    public static function initialize()
    {
        if (!self::$initialized) {
            self::$initialized = true;

            $info = new CoreInfo();

            /**
             * Version PHP sous forme x.x.x.x (exemple : 7.1.0).
             *
             * @var string
             */
            define('PASSION_ENGINE_PHP_MINIMUM_VERSION',
                   '7.1.0');

            /**
             * Version PHP sous forme x.x.x.x (exemple : 5.2.9.2).
             *
             * @var string
             */
            define('PASSION_ENGINE_PHP_VERSION',
                   $info->getPhpVersion());

            /**
             * Chemin jusqu'à la racine.
             *
             * @var string
             */
            define('PASSION_ENGINE_ROOT_DIRECTORY',
                   $info->getIndexDirectory());

            /**
             * Adresse URL complète jusqu'au moteur.
             *
             * @var string
             */
            define('PASSION_ENGINE_URL',
                   $info->getUrlAddress());

            /**
             * Le système d'exploitation qui exécute le moteur.
             *
             * @var string
             */
            define('PASSION_ENGINE_PHP_OS',
                   $info->getPhpOs());

            /**
             * Le retour de chariot du serveur hébergeant le moteur.
             *
             * @var string
             */
            define('PASSION_ENGINE_CRLF',
                   $info->getCrLf());

            /**
             * Numéro de version du moteur.
             *
             * contrôle de révision
             * XX -> version courante
             * XX -> fonctionnalités ajoutées
             * XX -> bugs ou failles critiques corrigés
             *
             * @var string
             */
            define('PASSION_ENGINE_VERSION',
                   '0.8.0');
        }
    }

    /**
     * Retourne la version du PHP.
     *
     * @return string
     */
    private function getPhpVersion()
    {
        return preg_replace('/[^0-9.]/',
                            '',
                            (preg_replace('/(_|-|[+])/',
                                          '.',
                                          phpversion())));
    }

    /**
     * Retourne le chemin jusqu'à la racine.
     *
     * @return string
     */
    private function getIndexDirectory()
    {
        $baseDir = '';

        // Recherche du chemin absolu depuis n'importe quel fichier
        if (defined('PASSION_ENGINE_BOOTSTRAP')) {
            // Nous sommes dans l'index
            $baseDir = getcwd();
        } else {
            $baseDir = $this->getIndexDirectoryFromCurrentFolder();
        }
        return $baseDir;
    }

    /**
     * Retourne le chemin jusqu'à la racine depuis le dossier actuel.
     *
     * @return string
     */
    private function getIndexDirectoryFromCurrentFolder()
    {
        $pathFromWorkingFolder = '';

        // Chemin de base
        $baseName = str_replace(self::getGlobalServer('SCRIPT_NAME'),
                                                      '',
                                                      self::getGlobalServer('SCRIPT_FILENAME'));

        if (!empty($baseName)) {
            $baseName = str_replace('/',
                                    DIRECTORY_SEPARATOR,
                                    $baseName);
            $workingDirectory = getcwd();

            if (!empty($workingDirectory)) {
                $pathFromWorkingFolder = $this->getPathFromWorkingFolder($baseName,
                                                                         $workingDirectory);
            }
        }

        // Verification du résultat
        $baseDir = '';

        if (!empty($pathFromWorkingFolder) && is_file($baseName . DIRECTORY_SEPARATOR . $pathFromWorkingFolder . DIRECTORY_SEPARATOR . 'Includes' . DIRECTORY_SEPARATOR . 'config.inc.php')) {
            $baseDir = $baseName . DIRECTORY_SEPARATOR . $pathFromWorkingFolder;
        } else if (is_file($baseName . DIRECTORY_SEPARATOR . 'Includes' . DIRECTORY_SEPARATOR . 'config.inc.php')) {
            $baseDir = $baseName;
        } else {
            $baseDir = $baseName;
        }
        return $baseDir;
    }

    /**
     * Retourne le chemin relatif jusqu'à la racine depuis le dossier actuel.
     *
     * @param string $baseName
     * @param string $workingDirectory
     * @return string
     */
    private function getPathFromWorkingFolder($baseName,
                                              $workingDirectory)
    {
        $path = '';

        // Nous isolons le chemin en plus jusqu'au fichier
        $path = str_replace($baseName,
                            '',
                            $workingDirectory);

        if (!empty($path)) {
            // Suppression du séparateur supplémentaire
            if ($path[0] === DIRECTORY_SEPARATOR) {
                $path = substr($path,
                               1);
            }

            // Vérification en se repérant sur l'emplacement du fichier de configuration
            while (!is_file($baseName . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR . 'Includes' . DIRECTORY_SEPARATOR . 'config.inc.php')) {
                // Nous remontons d'un cran
                $path = dirname($path);

                // La recherche n'aboutira pas
                if ($path === '.') {
                    break;
                }
            }
        }
        return $path;
    }

    /**
     * Retourne l'adresse URL complète jusqu'au moteur.
     *
     * @return string
     */
    private function getUrlAddress()
    {
        $protocolValue = 'http' . (!empty(self::getGlobalServer('HTTPS')) ? 's' : '');
        $serverName = self::getGlobalServer('SERVER_NAME');
        $queryUrl = $this->getQueryUrlAddress();
        return $protocolValue . '://' . $serverName . $queryUrl;
    }

    /**
     * Retourne l'adresse URL complète jusqu'au moteur.
     *
     * @return string
     */
    private function getQueryUrlAddress()
    {
        $rslt = '';

        // Recherche de l'URL courante
        $requestUri = self::getGlobalServer('REQUEST_URI');

        if (!empty($requestUri)) {
            $currentUrlArray = $this->getUrlAddressExploded($requestUri);

            // Recherche du dossier courant
            $urlBaseArray = explode(DIRECTORY_SEPARATOR,
                                    PASSION_ENGINE_ROOT_DIRECTORY);

            // Construction du lien
            $urlFinal = $this->getUrlAddressBuilded($currentUrlArray,
                                                    $urlBaseArray);
            $rslt = !empty($urlFinal) ? '/' . $urlFinal : '';
        }
        return $rslt;
    }

    /**
     * Retourne un tableau contenant les fragments de l'URL.
     *
     * @param string $requestUri
     * @return array
     */
    private function getUrlAddressExploded($requestUri)
    {
        if (substr($requestUri,
                   -1) === '/') {
            $requestUri = substr($requestUri,
                                 0,
                                 -1);
        }

        if ($requestUri[0] === '/') {
            $requestUri = substr($requestUri,
                                 1);
        }

        $requestUri = explode('/',
                              $requestUri);
        return $requestUri;
    }

    /**
     * Retourne l'adresse URL reconstruite.
     *
     * @param array $currentUrlArray
     * @param array $urlBaseArray
     * @return string
     */
    private function getUrlAddressBuilded($currentUrlArray,
                                          $urlBaseArray)
    {
        $urlFinal = '';
        $currentUrlCounter = count($currentUrlArray);
        $urlBaseCounter = count($urlBaseArray);

        for ($i = $currentUrlCounter - 1; $i >= 0; $i--) {
            for ($j = $urlBaseCounter - 1; $j >= 0; $j--) {
                if (empty($urlBaseArray[$j])) {
                    continue;
                }

                if ($currentUrlArray[$i] !== $urlBaseArray[$j]) {
                    break;
                }

                if (empty($urlFinal)) {
                    $urlFinal = $currentUrlArray[$i];
                } else {
                    $urlFinal = $currentUrlArray[$i] . '/' . $urlFinal;
                }

                $urlBaseArray[$j] = '';
            }
        }
        return $urlFinal;
    }

    /**
     * Retourne la plateforme sur lequel est PHP.
     *
     * @return string
     */
    private function getPhpOs()
    {
        return strtoupper(substr(PHP_OS,
                                 0,
                                 3));
    }

    /**
     * Retourne le retour chariot du serveur.
     *
     * @return string
     */
    private function getCrLf()
    {
        $rslt = '';

        // Le retour chariot de chaque OS
        if (PASSION_ENGINE_PHP_OS === 'WIN') {
            $rslt = "\r\n";
        } else if (PASSION_ENGINE_PHP_OS === 'MAC') {
            $rslt = "\r";
        } else {
            $rslt = "\n";
        }
        return $rslt;
    }

    /**
     * Classe autorisée à manipuler $_SERVER (lecture seule).
     *
     * @param string $keyName
     * @return string
     */
    private static function getGlobalServer($keyName)
    {
        return (isset(self::$unsafeGlobalVars['_SERVER']) && isset(self::$unsafeGlobalVars['_SERVER'][$keyName])) ? self::$unsafeGlobalVars['_SERVER'][$keyName] : '';
    }
}