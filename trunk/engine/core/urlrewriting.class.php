<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    new Core_Secure();
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
        if (Core_Main::doUrlRewriting()) {
            $this->canUse = $this->testPassed();
        }
    }

    /**
     * Instance du gestionnaire de réécriture du tampon de sortie.
     *
     * @return Core_UrlRewriting
     */
    public static function &getInstance() {
        if (self::$coreUrlRewriting == null) {
            self::$coreUrlRewriting = new self();
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
