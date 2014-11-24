<?php

namespace TREngine\Engine\Lib;

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
    public function &render($callback = "BlockMenu::getLine") {
        $route = array();

        // Creation du tableau route
        if (is_object($this->items[$this->itemActive])) {
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
                if ($key === $route[0]) {
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
     * Chargement du menu via le cache.
     */
    private function loadFromCache() {
        $data = CoreCache::getInstance(CoreCache::SECTION_MENUS)->readCache($this->identifier . ".php");
        $this->items = unserialize(ExecEntities::stripSlashes($data));
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

            $coreCache = CoreCache::getInstance(CoreCache::SECTION_MENUS);
            $coreCache->writeCache(
            $this->identifier . ".php", $coreCache->serializeData(serialize($this->items))
            );
        }
    }

}
