<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

/**
 * Classe de mise en forme d'onglets.
 *
 * @author Sébastien Villemain
 */
class Libs_Tabs {

    /**
     * Vérifie si c'est la 1ère instance.
     *
     * @var boolean
     */
    private static $firstInstance = true;

    /**
     * Nom du groupe d'onglets.
     *
     * @var string
     */
    private $name = "";

    /**
     * Groupe d'onglets (HTML).
     *
     * @var string
     */
    private $tabs = "";

    /**
     * Groupe de contenu des onglets (HTML).
     *
     * @var unknown_type
     */
    private $tabsContent = "";

    /**
     * Id de l'onglet sélectionné.
     *
     * @var string
     */
    private $selected = "";

    /**
     * Compteur d'onglet
     *
     * @var int
     */
    private $tabCounter = 0;

    /**
     * Création d'un nouveau groupe d'onglet.
     *
     * @param string $name Nom du groupe d'onglet
     */
    public function __construct($name) {
        $this->name = $name;
        $this->selected = Core_Request::getString("selectedTab");

        if (self::$firstInstance) {
            Exec_JQuery::getIdTabs();
            self::$firstInstance = false;
        }

        if (empty($this->selected) && !Core_Html::getInstance()->javascriptEnabled()) {
            $this->selected = $this->name . "idTab0";
        }
    }

    /**
     * Ajouter un onglet et son contenu.
     *
     * @param string $title titre de l'onglet
     * @param string $htmlContent contenu de l'onglet
     */
    public function addTab($title, $htmlContent) {
        // Id de l'onget courant
        $idTab = $this->name . "idTab" . $this->tabCounter++;

        // Création de l'onget
        $this->tabs .= "<li>";

        // Un lien complet sans le javascript window.location = ""#" . $idTab";
        $queryString = Core_Request::getString("QUERY_STRING", "", "SERVER");
        $queryString = str_replace("selectedTab = " . $this->selected, "", $queryString);
        $queryString = (substr($queryString, -1) != "&") ? $queryString . "&" : $queryString;
        $queryString = "index.php?" . $queryString . "selectedTab = " . $idTab;

        // TODO A Vérifier
        $this->tabs .= Core_Html::getLinkWithAjax($queryString, "#" . $idTab, $idTab, Exec_Entities::textDisplay($title), (($this->selected == $idTab) ? "class=\"selected\"" : "display=\"none;\""));
//        $this->tabs .= Core_Html::getLink($queryString, false, Exec_Entities::textDisplay($title), "window.location=\"#" . $idTab . "\";", (($this->selected == $idTab) ? "class=\"selected\"" : "display=\"none;\""));

        $this->tabs .= "</li>";

        // Si le javascript est actif ou que nous sommes dans l'onget courant
        if (Core_Html::getInstance()->javascriptEnabled() || $this->selected == $idTab) {
            $this->tabsContent .= "<div id=\"" . $idTab . "\">" . $htmlContent . "</div>";
        }
    }

    /**
     * Retourne le rendu du form complet.
     *
     * @param string $class
     * @return string
     */
    public function &render($class = "") {
        $content = "<div id=\"" . $this->name . "\""
        . " class=\"" . ((!empty($class)) ? $class : "tabs") . "\">"
        . "<ul class=\"idTabs\">"
        . $this->tabs
        . "</ul>"
        . $this->tabsContent
        . "</div>";
        return $content;
    }

}