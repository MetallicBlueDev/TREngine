<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Editeur du fil d'Ariane.
 *
 * @author Sébastien Villemain
 */
class LibsBreadcrumb {

    /**
     * Instance de la classe.
     *
     * @var LibsBreadcrumb
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
        $this->addTrail(CoreMain::getInstance()->getDefaultSiteName(), "index.php");
    }

    /**
     * Retoune l'instance LibsBreadcrumb.
     *
     * @return LibsBreadcrumb
     */
    public static function &getInstance() {
        if (self::$libsBreadcrumb === null) {
            self::$libsBreadcrumb = new LibsBreadcrumb();
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
                $trail = CoreHtml::getLink($link, $trail);
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
