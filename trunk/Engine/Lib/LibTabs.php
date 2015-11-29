<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreRequest;
use TREngine\Engine\Core\CoreHtml;
use TREngine\Engine\Exec\ExecEntities;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire d'onglet.
 *
 * @author Sébastien Villemain
 */
class LibTabs {

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
    private $tabsBuffer = "";
    private $tabsContentBuffer = "";

    /**
     * Identifiant de l'onglet sélectionné.
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
        $this->selected = CoreRequest::getString("selectedTab");

        if (empty($this->selected)) {
            $this->selected = $this->getTabId();
        }

        CoreHtml::getInstance()->addJavascript("
            $('#" . $this->name . " ul li').click(function() {
                $('#" . $this->name . " div').hide();
                var activeTab = $(this).find('input').attr('id');
                var activeTabId = '#' + activeTab.toString().replace('tab_', 'tab_content_');
                $(activeTabId).show();
            });");
    }

    /**
     * Ajouter un onglet et son contenu.
     *
     * @param string $title Le titre de l'onglet
     * @param string $htmlContent Le contenu de l'onglet
     */
    public function addTab($title, $htmlContent) {
        $tabId = $this->getTabId();
        $tabSelected = ($this->selected === $tabId);

        $this->tabsBuffer .= "<li>"
        . "<input type=\"radio\" name=\"" . $this->getTabsName() . "\" id=\"" . $tabId . "\""
        . ($tabSelected ? " checked" : "") . ">"
        . "<label for=\"" . $tabId . "\">"
        . ExecEntities::textDisplay($title)
        . "</label>"
        . "</li>";

        $purHtml = "";

        if (!CoreHtml::getInstance()->javascriptEnabled() || $tabSelected) {
            $purHtml = " style=\"display: block;\"";
        }
        $this->tabsContentBuffer .= "<div id=\"" . $this->getTabContentId() . "\"" . $purHtml . ">" . $htmlContent . "</div>";
        $this->tabCounter++;
    }

    /**
     * Retourne le rendu du groupe d'onglet.
     *
     * @param string $class
     * @return string
     */
    public function &render($class = "") {
        $content = "<div id=\"" . $this->name . "\" class=\"" . ((!empty($class)) ? $class : "tabs") . "\">"
        . "<ul>"
        . $this->tabsBuffer
        . "</ul>"
        . $this->tabsContentBuffer
        . "</div>";
        return $content;
    }

    /**
     * Retourne le nom du formulaire lié.
     *
     * @return string
     */
    private function getTabsName() {
        return "tabs_" . $this->name;
    }

    /**
     * Retourne l'identifiant de l'onglet courant.
     *
     * @return string
     */
    private function getTabId() {
        return "tab_" . $this->name . "_" . $this->tabCounter;
    }

    /**
     * Retourne l'identifiant du contenu de l'onglet courant.
     *
     * @return string
     */
    private function getTabContentId() {
        return "tab_content_" . $this->name . "_" . $this->tabCounter;
    }

}
