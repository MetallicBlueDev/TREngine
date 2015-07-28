<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreMain;
use TREngine\Engine\Core\CoreHtml;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Editeur du fil d'Ariane.
 *
 * @author Sébastien Villemain
 */
class LibBreadcrumb {

    /**
     * Instance de la classe.
     *
     * @var LibBreadcrumb
     */
    private static $libBreadcrumb = null;

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
     * Retoune l'instance LibBreadcrumb.
     *
     * @return LibBreadcrumb
     */
    public static function &getInstance() {
        if (self::$libBreadcrumb === null) {
            self::$libBreadcrumb = new LibBreadcrumb();
        }
        return self::$libBreadcrumb;
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
     * @return string
     */
    public function &getBreadcrumbTrail() {
        $rslt = "<aside class=\"breadcrumbtrail\"><ul>";

        foreach ($this->breadcrumbTrail as $trail) {
            $rslt .= "<li>" . $trail . "</li>";
        }
        $rslt.= "</ul></aside>";
        return $rslt;
    }

}
