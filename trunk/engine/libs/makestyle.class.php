<?php
if (!defined("TR_ENGINE_INDEX")) {
    exit();
}

/**
 * Make Style, moteur de template PHP.
 * RAPIDE, SIMPLE ET EFFICACE !
 *
 * @author Sébastien Villemain
 */
class Libs_MakeStyle {

    /**
     * Dossier contenant les templates.
     *
     * @var String
     */
    private static $templatesDir = "templates";

    /**
     * Nom du dossier du template utilisé.
     *
     * @var String
     */
    private static $currentTemplate = "default";

    /**
     * Nom du fichier template.
     *
     * @var String
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
     * Nouveau template.
     *
     * @param $fileName String nom du template
     */
    public function __construct($fileName = "") {
        $this->debugMode = false;
        $this->setFileName($fileName);

        // TODO si $templatesDir ou $currentTemplate est vide, le moteur plante !
        if (empty(self::$templatesDir) || empty(self::$currentTemplate)) {
            Core_Secure::getInstance()->throwException("makeStyleConfig", array(
                "templatesDir = " . self::$templatesDir,
                "currentTemplate = " . self::$currentTemplate));
        }
    }

    /**
     * Assigne une valeur au template.
     *
     * @param String $key Nome de la variable
     * @param String or Libs_MakeStyle $value Valeur de la variable
     */
    public function assign($key, $value) {
        $this->fileVars[$key] = is_object($value) ? $value->display() : $value;
    }

    /**
     * Exécute et affiche le template.
     *
     * @param String $fileName
     * @param boolean $debugMode Si le fichier de template debug n'est pas trouvé, le fichier debug par défaut est utilisé.
     * @return $output L'affichage finale du template
     */
    public function display($fileName = "", $debugMode = false) {
        if ($debugMode) {
            $this->setFileName($fileName);

            // Si le template ne contient pas le fichier debug
            if (!$this->isTemplate()) {
                // Activation du mode debug
                $this->debugMode = true;
            }
        }

        echo $this->render($fileName);
    }

    /**
     * Retourne le rendu du template.
     *
     * @param String $fileName
     * @return String
     */
    public function &render($fileName = "") {
        $this->setFileName($fileName);

        // Vérification du template
        if (!$this->isTemplate()) {
            Core_Secure::getInstance()->throwException("makeStyle", $this->getTemplatePath());
        }

        // Extrait les variables en local
        extract($this->fileVars);

        // Traitement du template
        ob_start();
        include($this->getTemplatePath());
        $output = ob_get_contents();
        ob_end_clean();
        return $output;
    }

    /**
     * Configure le dossier contenant les templates.
     *
     * @param String $templatesDir
     */
    public static function setTemplatesDir($templatesDir) {
        if (is_dir(TR_ENGINE_DIR . "/" . $templatesDir)) {
            self::$templatesDir = $templatesDir;
        }
    }

    /**
     * Configure le dossier du template courament utilisé.
     *
     * @param String $currentTemplate
     */
    public static function setCurrentTemplate($currentTemplate) {
        if (is_dir(TR_ENGINE_DIR . "/" . self::$templatesDir . "/" . $currentTemplate)) {
            self::$currentTemplate = $currentTemplate;
        }
    }

    /**
     * Retourne le dossier vers les templates.
     *
     * @return String
     */
    public static function &getTemplatesDir() {
        return self::$templatesDir;
    }

    /**
     * Retourne le dossier du template utilisé.
     *
     * @return String
     */
    public static function &getCurrentTemplate() {
        return self::$currentTemplate;
    }

    /**
     * Retourne la liste de templates disponibles.
     *
     * @return array
     */
    public static function &listTemplates() {
        $templates = array();

        // Vérification du dossier template
        if (is_dir(TR_ENGINE_DIR . "/" . self::$templatesDir)) {
            $templates = Core_CacheBuffer::listNames(self::$templatesDir);
        }
        return $templates;
    }

    /**
     * Affecte le nom du fichier représentant le template.
     *
     * @param String $fileName Nom du fichier
     */
    private function setFileName($fileName) {
        if (!empty($fileName) && $this->fileName != $fileName) {
            if (substr($fileName, -4) != ".php") {
                $fileName &= ".php";
            }

            $this->fileName = $fileName;
        }
    }

    /**
     * Retourne le chemin jusqu'au template
     *
     * @return String path
     */
    private function &getTemplatePath() {
        // Si le mode debug est activé, on utilise le fichier par défaut
        $path = "";

        if ($this->debugMode) {
            $path = TR_ENGINE_DIR . "/engine/libs/makestyle.debug";
        } else {
            $path = TR_ENGINE_DIR . "/" . self::$templatesDir . "/" . self::$currentTemplate . "/" . $this->fileName;
        }
        return $path;
    }

    /**
     * Vérifie la validité du template
     *
     * @return boolean true si le chemin du template est valide
     */
    private function isTemplate() {
        return is_file($this->getTemplatePath());
    }

}
