<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

/**
 * Gestionnaire de menu.
 *
 * @author Sébastien Villemain
 */
class Libs_Menu {

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
        $this->itemActive = Core_Request::getInt("item", 0);

        Core_CacheBuffer::setSectionName("menus");

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
    public function &render($callback = "Block_Menu::getLine") {
        $route = array();

        // Creation du tableau route
        if (is_object($this->items[$this->itemActive])) {
            $route = $this->items[$this->itemActive]->getRoute();
        }

        // Début de rendu
        $out = "<ul id=\"" . $this->identifier . "\"" . $this->attribtus . ">";
        foreach ($this->items as $key => $item) {
            if ($item->getParentId() == 0 && Core_Access::autorize($this->identifier, $item->getRank())) {
                // Ajout du tableau route dans l'élément principal
                if ($key == $route[0]) {
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
        $data = Core_CacheBuffer::getCache($this->identifier . ".php");
        $this->items = unserialize(Exec_Entities::stripSlashes($data));
    }

    /**
     * Vérifie la présence du cache.
     *
     * @return boolean
     */
    private function isCached() {
        return (Core_CacheBuffer::cached($this->identifier . ".php"));
    }

    /**
     * Chargement du menu depuis la base.
     *
     * @param array $sql parametre de Sélection
     */
    private function loadFromDb(array $sql) {
        Core_Sql::getInstance()->select(
        $sql['table'], $sql['select'], $sql['where'], $sql['orderby'], $sql['limit']
        );

        if (Core_Sql::getInstance()->affectedRows() > 0) {
            // Création d'un buffer
            Core_Sql::getInstance()->addArrayBuffer($this->identifier, "menu_id");
            $menus = Core_Sql::getInstance()->getBuffer($this->identifier);

            // Ajoute et monte tout les items
            foreach ($menus as $key => $item) {
                $newElement = new Libs_MenuElement($item, $this->items);
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

            Core_CacheBuffer::writingCache(
            $this->identifier . ".php", Core_CacheBuffer::serializeData(serialize($this->items))
            );
        }
    }

}
