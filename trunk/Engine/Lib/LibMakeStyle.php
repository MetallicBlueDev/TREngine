<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreSecure;
use TREngine\Engine\Core\CoreCache;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Make Style, moteur de template PHP.
 * RAPIDE, SIMPLE ET EFFICACE !
 *
 * @author Sébastien Villemain
 */
class LibMakeStyle {

    /**
     * Dossier contenant le template.
     *
     * @var string
     */
    private static $templateDir = "Engine/Template/MetallicBlueSky";

    /**
     * Nom du fichier template.
     *
     * @var string
     */
    private $fileName = "";

    /**
     * Variables assignées.
     *
     * @var array
     */
    private $fileVars = array();

    /**
     * Indique si l'instance courante est en mode debug.
     *
     * @var boolean
     */
    private $debugMode = false;

    /**
     * Détermine si le template est valide.
     *
     * @var boolean
     */
    private $valid = null;

    /**
     * Nouveau template.
     *
     * @param string $fileName nom du template
     */
    public function __construct($fileName = "") {
        $this->setFileName($fileName);
    }

    /**
     * Assigne une valeur au template.
     *
     * @param string $key Nome de la variable
     * @param string or LibMakeStyle $value Valeur de la variable
     */
    public function assign($key, $value) {
        $this->fileVars[$key] = is_object($value) ? $value->display() : $value;
    }

    /**
     * Exécute et affiche le template.
     *
     * @param string $fileName
     * @param boolean $debugMode Si le fichier de template debug n'est pas trouvé, le fichier debug par défaut est utilisé.
     * @return $output L'affichage finale du template
     */
    public function display($fileName = "", $debugMode = false) {
        if ($debugMode) {
            $this->setFileName($fileName);

            // Le template ne contient pas le fichier debug
            if (!$this->isTemplate()) {
                // Activation du mode debug
                $this->setDebugMode();
            }
        }

        echo $this->render($fileName);
    }

    /**
     * Retourne le rendu du template.
     *
     * @param string $fileName
     * @return string
     */
    public function &render($fileName = "") {
        $this->setFileName($fileName);

        // Vérification du template
        if (!$this->isTemplate()) {
            if ($this->debugMode) {
                exit("CRITICAL ERROR: DEBUG TEMPLATE NOT FOUND.");
            }

            CoreSecure::getInstance()->throwException("makeStyle", null, array(
                $this->getTemplatePath()));
        }

        // Extrait les variables en local
        extract($this->fileVars);

        // Traitement du template
        ob_start();
        include $this->getTemplatePath();
        $output = ob_get_clean();
        return $output;
    }

    /**
     * Configure le dossier contenant les templates.
     *
     * @param string $templateDir
     */
    public static function setTemplateDir($templateDir) {
        if (!self::isTemplateDir($templateDir)) {
            CoreSecure::getInstance()->throwException("makeStyleConfig", null, array(
                "templateDir = " . $templateDir));
        }

        self::$templateDir = $templateDir;
    }

    /**
     * Retourne le dossier vers les templates.
     *
     * @return string
     */
    public static function &getTemplateDir() {
        return self::$templateDir;
    }

    /**
     * Retourne la liste de templates disponibles.
     *
     * @return array
     */
    public static function &getTemplateList() {
        $templates = array();
        $templatesDir = array(
            "Custom" . DIRECTORY_SEPARATOR . "Template",
            "Engine" . DIRECTORY_SEPARATOR . "Template");

        foreach ($templatesDir as $templateDir) {
            if (self::isTemplateDir($templateDir)) {
                $templates = array_merge($templatesDir, CoreCache::getInstance()->getNameList($templateDir));
            }
        }
        return $templates;
    }

    /**
     * Détermine si le dossier contenant le template est valide.
     *
     * @param string $templateDir
     * @return boolean
     */
    public static function isTemplateDir($templateDir) {
        return !empty($templateDir) && is_dir(TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . $templateDir);
    }

    /**
     * Affecte le nom du fichier représentant le template.
     *
     * @param string $fileName Nom du fichier
     */
    private function setFileName($fileName) {
        if (!empty($fileName) && $this->fileName !== $fileName) {
            if (substr($fileName, -4) !== ".php") {
                $fileName .= ".php";
            }

            $this->fileName = $fileName;
            $this->valid = null;
        }
    }

    /**
     * Activation du mode debug.
     */
    private function setDebugMode() {
        $this->debugMode = true;
        $this->valid = null;
    }

    /**
     * Retourne le chemin jusqu'au template
     *
     * @return string path
     */
    private function &getTemplatePath() {
        $path = "";

        if ($this->debugMode) {
            // En debug mode, on utilise le fichier par défaut
            $path = TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . "Engine" . DIRECTORY_SEPARATOR . "Template" . DIRECTORY_SEPARATOR . "makestyle.debug.php";
        } else {
            $path = TR_ENGINE_INDEXDIR . DIRECTORY_SEPARATOR . self::$templateDir . DIRECTORY_SEPARATOR . $this->fileName;
        }
        return $path;
    }

    /**
     * Vérifie la validité du template
     *
     * @return boolean true si le chemin du template est valide
     */
    private function isTemplate() {
        if ($this->valid === null) {
            $this->valid = is_file($this->getTemplatePath());
        }
        return $this->valid;
    }

}
