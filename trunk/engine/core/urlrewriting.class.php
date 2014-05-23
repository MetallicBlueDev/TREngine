<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Gestionnaire de réécriture du tampon de sortie.
 *
 * @author Sébastien Villemain
 */
class Core_UrlRewriting {

    /**
     * Gestionnnaire de réécriture.
     *
     * @var Core_UrlRewriting
     */
    private static $coreUrlRewriting = null;

    /**
     * Vérifie si l'url rewriting a été activée.
     *
     * @var boolean
     */
    private $canUse = false;

    /**
     * Nouveau gestionnaire.
     */
    private function __construct() {
        if (Core_Main::getInstance()->doUrlRewriting()) {
            $this->canUse = $this->testPassed();
        }
    }

    /**
     * Instance du gestionnaire de réécriture du tampon de sortie.
     *
     * @return Core_UrlRewriting
     */
    public static function &getInstance() {
        if (self::$coreUrlRewriting === null) {
            self::$coreUrlRewriting = new Core_UrlRewriting();
        }
        return self::$coreUrlRewriting;
    }

    public function &rewriteLink($link) {
        if ($this->canUse) {

        }
        return $link;
    }

    public function &rewriteBuffer($buffer) {
        if ($this->canUse) {

        }
        return $buffer;
    }

    /**
     * Obtention d'une adresse URL complète.
     *
     * @param string $link Adresse URL à réécrire.
     * @param boolean $layout true ajouter le layout.
     * @return string
     */
    public static function &getLink($link, $layout = false) {
        // Configuration du layout
        if ($layout) {
            $layout = "&amp;layout=";

            if (strpos($link, "blockId=") !== false) {
                $layout .= "block";
            } else if (strpos($link, "mod=") !== false) {
                $layout .= "module";
            } else {
                $layout .= "default";
            }

            $link .= $layout;
        }

        // Recherche de la page principal
        if (strpos($link, "index.php") === false) {
            if ($link[0] === "?") {
                $link = "index.php" . $link;
            } else {
                $link = "index.php?" . $link;
            }
        }

        // Finalise la réécriture du lien
        return self::getInstance()->rewriteLink($link);
    }

    /**
     * Vérifie si les tests ont été passés avec succès.
     *
     * @return boolean
     */
    private function &testPassed() {
        $rslt = false;
        // TODO vérifie si fichier tmp de test est OK
        // si pas OK et pas de fichier tmp pour signaler la désactivation
        // on tente de mettre urlRewriting a 0 puis on créé le fichier tmp de désactivation
        // si déjà fichier tmp de décastivation ajouter erreur dans Core_Logger
        return $rslt;
    }

}
