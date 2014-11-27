<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreHtml;
use TREngine\Engine\Core\CoreAccessType;
use TREngine\Engine\Core\CoreAccess;
use TREngine\Engine\Core\CoreRequest;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Exec\ExecEntities;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de menu.
 *
 * @author Sébastien Villemain
 */
class LibMenu {

    /**
     * Balise pour les options.
     *
     * @var string
     */
    const OPTIONS_TAG = "__OPTIONS__";

    /**
     * Identifiant du menu.
     *
     * @var string
     */
    private $identifier = "";

    /**
     * Menu complet et son arborescence.
     *
     * @var array
     */
    private $items = array();

    /**
     * Clès de l'élément actuellement actif.
     *
     * @var int
     */
    private $itemActive = 0;

    /**
     * Attributs de l'élément principal.
     *
     * @var string
     */
    private $attribtus = "";

    /**
     * Construction du menu.
     *
     * @param string $identifier Identifiant du menu par exemple "block22"
     * @param array $sql
     */
    public function __construct($identifier, array $sql = array()) {
        $this->identifier = $identifier;
        $this->itemActive = CoreRequest::getInteger("item", 0);

        if ($this->isCached()) {
            $this->loadFromCache();
        } else if (count($sql) >= 3) {
            $this->loadFromDb($sql);
        }
    }

    /**
     * Ajoute un attribut à l'élément UL principal.
     *
     * @param string $name
     * @param string $value
     */
    public function addAttributs($name, $value) {
        $this->attribtus .= " " . $name . "=\"" . $value . "\"";
    }

    /**
     * Création d'un rendu complet du menu.
     *
     * @param string $callback
     * @return string
     */
    public function &render($callback = "LibMenu::getLine") {
        $route = array();

        // Creation du tableau route
        if (isset($this->items[$this->itemActive]) && is_object($this->items[$this->itemActive])) {
            $route = $this->items[$this->itemActive]->getRoute();
        }

        // Début de rendu
        $out = "<ul id=\"" . $this->identifier . "\"" . $this->attribtus . ">";
        foreach ($this->items as $key => $item) {
            $infos = array(
                "zone" => "MENU",
                "rank" => $item->getRank(),
                "identifiant" => $this->identifier);

            if ($item->getParentId() == 0 && CoreAccess::autorize(CoreAccessType::getTypeFromDatabase($infos))) {
                // Ajout du tableau route dans l'élément principal
                if (isset($route[0]) && $key === $route[0]) {
                    $item->setRoute($route);
                }

                // Création du rendu
                $out .= $this->items[$key]->toString($callback);
            }
        }
        $out .= "</ul>";
        return $out;
    }

    /**
     * Retourne une ligne de menu propre sous forme HTML
     *
     * Exemple :
     * Link example__OPTIONS__B.I.U.A.?mod=home__OPTIONS__
     *
     * @param $line string
     * @return string
     */
    public static function getLine($line) {
        $output = "";
        $matches = null;

        if (preg_match("/(.+)" . self::OPTIONS_TAG . "(.*?)" . self::OPTIONS_TAG . "/", $line, $matches)) {
            // Conversion du texte
            $text = ExecEntities::textDisplay($matches[1]);

            $bold = false;
            $italic = false;
            $underline = false;
            $big = false;
            $small = false;
            $popup = false;
            $link = "";

            // Recherche des options et style
            $options = explode(".", $matches[2]);
            foreach ($options as $key => $value) {
                switch ($value) {
                    case "B":
                        $bold = true;
                        break;
                    case "I":
                        $italic = true;
                        break;
                    case "U":
                        $underline = true;
                        break;
                    case "BIG":
                        $big = true;
                        break;
                    case "SMALL":
                        $small = true;
                        break;
                    case "A":
                        $link = $options[$key + 1];
                        break;
                    case "POPUP":
                        $popup = true;
                        break;
                }
            }

            // Application des options et styles
            if ($bold) {
                $text = "<b>" . $text . "</b>";
            }
            if ($italic) {
                $text = "<i>" . $text . "</i>";
            }
            if ($underline) {
                $text = "<u>" . $text . "</u>";
            }
            if ($big) {
                $text = "<big>" . $text . "</big>";
            }
            if ($small) {
                $text = "<small>" . $text . "</small>";
            }
            if (!empty($link)) {
                $text = CoreHtml::getLink($link, $text, false, ($popup ? "window.open('" . $link . "');return false;" : ""));
            }

            $output = $text;
        } else {
            // Aucun style appliquer
            // Conversion du texte
            $output = ExecEntities::textDisplay($line);
        }
        return $output;
    }

    /**
     * Retourne une ligne avec les TAGS
     *
     * @param $text string Texte du menu
     * @param $options array Options choisis
     * @return string
     */
    public static function setLine($text, $options = array()) {
        $optionsString = "";

        // Formate les options
        foreach ($options as $key => $value) {
            // Les options sont uniquement en majuscule
            $key = strtoupper($key);

            if ($key == "B") {
                $optionsString .= "B";
            }
            if ($key == "I") {
                $optionsString .= "I";
            }
            if ($key == "I") {
                $optionsString .= "U";
            }
            if ($key == "BIG") {
                $optionsString .= "BIG";
            }
            if ($key == "SMALL") {
                $optionsString .= "SMALL";
            }
            if ($key == "I") {
                $optionsString .= "A." . $value;
            }
            if ($key == "POPUP") {
                $optionsString .= "POPUP";
            }
        }

        // Termine le tag des options
        if (!empty($optionsString)) {
            $optionsString = self::OPTIONS_TAG . $optionsString . self::OPTIONS_TAG;
        }

        return $text . $optionsString;
    }

    /**
     * Chargement du menu via le cache.
     */
    private function loadFromCache() {
        $this->items = CoreCache::getInstance(CoreCache::SECTION_MENUS)->readCacheAndUnserialize($this->identifier . ".php");
    }

    /**
     * Vérifie la présence du cache.
     *
     * @return boolean
     */
    private function isCached() {
        return (CoreCache::getInstance(CoreCache::SECTION_MENUS)->cached($this->identifier . ".php"));
    }

    /**
     * Chargement du menu depuis la base.
     *
     * @param array $sql parametre de Sélection
     */
    private function loadFromDb(array $sql) {
        $coreSql = CoreSql::getInstance();

        $coreSql->select(
        $sql['table'], $sql['select'], $sql['where'], $sql['orderby'], $sql['limit']
        );

        if ($coreSql->affectedRows() > 0) {
            // Création d'un buffer
            $coreSql->addArrayBuffer($this->identifier, "menu_id");
            $menus = $coreSql->getBuffer($this->identifier);

            // Ajoute et monte tout les items
            foreach ($menus as $key => $item) {
                $newElement = new LibMenuElement($item, $this->items);
                $newElement->setRoute(array(
                    $key));
                $this->items[$key] = $newElement;
            }

            // Création du chemin
            foreach ($this->items as $key => $item) {
                if ($item->getParentId() > 0) {
                    $item->setRoute($this->items[$item->getParentId()]->getRoute());
                }
            }

            CoreCache::getInstance(CoreCache::SECTION_MENUS)->writeCacheAndSerialize($this->identifier . ".php", $this->items);
        }
    }

}
