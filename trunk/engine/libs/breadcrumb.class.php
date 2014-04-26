<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

/**
 * Editeur du fil d'Ariane.
 *
 * @author Sébastien Villemain
 */
class Libs_Breadcrumb {

    /**
     * Instance de la classe.
     *
     * @var Libs_Breadcrumb
     */
    private static $libsBreadcrumb = null;

    /**
     * Le fil d'Ariane.
     *
     * @var array
     */
    private $breadcrumbTrail = array();

    private function __construct() {
        // Ajoute la page principal
        $this->addTrail(Core_Main::getInstance()->getDefaultSiteName(), "index.php");
    }

    /**
     * Retoune l'instance Libs_Breadcrumb.
     *
     * @return Libs_Breadcrumb
     */
    public static function &getInstance() {
        if (self::$libsBreadcrumb === null) {
            self::$libsBreadcrumb = new self();
        }
        return self::$libsBreadcrumb;
    }

    /**
     * Ajoute un tracé au fil d'Ariane.
     *
     * @param string $trail
     * @param string $link
     */
    public function addTrail($trail, $link = "") {
        if (!empty($trail)) {
            $constant = "TRAIL_" . strtoupper($trail);

            if (defined($constant)) {
                $trail = constant($constant);
            }

            if (!empty($link)) {
                $trail = "<a href=\"" . Core_Html::getLink($link) . "\">" . $trail . "</a>";
            }

            $this->breadcrumbTrail[] = $trail;
        }
    }

    /**
     * Retourne le fil d'Ariane complet.
     *
     * @param string $separator séparateur de tracé
     * @return string
     */
    public function &getBreadcrumbTrail($separator = " >> ") {
        $rslt = "";

        foreach ($this->breadcrumbTrail as $trail) {
            if (!empty($rslt)) {
                $rslt .= $separator;
            }

            $rslt .= $trail;
        }
        return $rslt;
    }

}
