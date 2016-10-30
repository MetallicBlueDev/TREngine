<?php

namespace TREngine\Engine\Lib;

use TREngine\Engine\Core\CoreHtml;
use TREngine\Engine\Core\CoreAccessType;
use TREngine\Engine\Core\CoreAccess;
use TREngine\Engine\Core\CoreRequest;
use TREngine\Engine\Core\CoreSql;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Exec\ExecEntities;
use TREngine\Engine\Exec\ExecUtils;

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
     * Identifiant de l'élément actuellement actif.
     *
     * @var int
     */
    private $activeItemId = 0;

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
        $this->activeItemId = CoreRequest::getInteger("item", 0);

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
        $activeTree = array();
        $activeItem = $this->getActiveItem();

        if ($activeItem !== null) {
            $activeTree = $activeItem->getTree();
        }

        foreach ($this->items as $key => $item) {
            $item->setActive(ExecUtils::inArray($key, $activeTree, true));
        }

        // Début de rendu
        $out = "<ul id=\"" . $this->identifier . "\"" . $this->attribtus . ">";

        // Rendu des branches principaux
        foreach ($this->items as $key => $item) {
            if ($item->getParentId() == 0) {
                $infos = array(
                    "zone" => "MENU",
                    "rank" => $item->getRank(),
                    "identifiant" => $this->identifier);

                if (CoreAccess::autorize(CoreAccessType::getTypeFromDatabase($infos))) {
                    $out .= $this->items[$key]->toString($callback);
                }
            }
        }
        $out .= "</ul>";
        return $out;
    }

    /**
     * Retourne une ligne de menu propre sous forme HTML
     *
     * Exemple :
     * Link example__OPTIONS__BOLD.ITALIC.UNDERLINE.A.?module=home__OPTIONS__
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
            $link = false;

            // Recherche des options et style
            $options = explode(".", $matches[2]);

            // Application des options et styles
            foreach ($options as $key => $value) {
                switch ($value) {
                    case "BOLD":
                        if (!$bold) {
                            $text = "<span class=\"text_bold\">" . $text . "</span>";
                            $bold = true;
                        }
                        break;
                    case "ITALIC":
                        if (!$italic) {
                            $text = "<span class=\"text_italic\">" . $text . "</span>";
                            $italic = true;
                        }
                        break;
                    case "UNDERLINE":
                        if (!$underline) {
                            $text = "<span class=\"text_underline\">" . $text . "</span>";
                            $underline = true;
                        }
                        break;
                    case "BIG":
                        if (!$big) {
                            $text = "<span class=\"text_big\">" . $text . "</span>";
                            $big = true;
                        }
                        break;
                    case "SMALL":
                        if (!$small) {
                            $text = "<span class=\"text_small\">" . $text . "</span>";
                            $small = true;
                        }
                        break;
                    case "A":
                        if (!$link) {
                            $linkValue = (isset($options[$key + 1]) ? $options[$key + 1] : null);

                            if (!empty($linkValue)) {
                                $text = CoreHtml::getLink($linkValue, $text, false, ($popup ? "window.open('" . $linkValue . "');return false;" : ""));
                            }
                            $link = true;
                        }
                        break;
                    case "POPUP":
                        $popup = true;
                        break;
                }
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

            if ($key == "BOLD") {
                $optionsString .= "BOLD";
            }
            if ($key == "ITALIC") {
                $optionsString .= "ITALIC";
            }
            if ($key == "UNDERLINE") {
                $optionsString .= "UNDERLINE";
            }
            if ($key == "BIG") {
                $optionsString .= "BIG";
            }
            if ($key == "SMALL") {
                $optionsString .= "SMALL";
            }
            if ($key == "A") {
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
     * Retourne l'élément actif.
     *
     * @return LibMenuElement
     */
    private function getActiveItem() {
        $item = null;

        if (isset($this->items[$this->activeItemId]) && is_object($this->items[$this->activeItemId])) {
            $item = $this->items[$this->activeItemId];
        }
        return $item;
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
     * @return bool
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
            // Création d'un buffer pour les menus
            $coreSql->addArrayBuffer($this->identifier, "menu_id");
            $menus = $coreSql->getBuffer($this->identifier);

            // Création de tous les menus
            foreach ($menus as $key => $item) {
                $this->items[$key] = new LibMenuElement($item);
            }

            // Création du chemin des menus
            foreach ($this->items as $item) {
                // Détermine le type de branche
                if ($item->getParentId() > 0) {
                    // Enfant d'une branche
                    $item->addAttributs("class", "item" . $item->getMenuId());
                    $this->items[$item->getParentId()]->addChild($item);
                } else if ($item->getParentId() == 0) {
                    // Branche principal
                    $item->addAttributs("class", "parent");
                }

                // Termine le chemin du menu avec son propre identifiant
                $tree = $item->getTree();
                $tree[] = $item->getMenuId();
                $item->setTree($tree);
            }

            CoreCache::getInstance(CoreCache::SECTION_MENUS)->writeCacheAndSerialize($this->identifier . ".php", $this->items);
        }
    }

}
