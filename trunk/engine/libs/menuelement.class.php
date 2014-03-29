<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../core/secure.class.php");
    new Core_Secure();
}

/**
 * Membre d'un menu.
 *
 * @author Sébastien Villemain
 */
class Libs_MenuElement {

    /**
     * Item info du menu.
     *
     * @var array - object
     */
    public $data = array();

    /**
     * Attributs de l'élément.
     *
     * @var array
     */
    private $attributs = array();

    /**
     * Enfant de l'élément.
     *
     * @var array
     */
    private $child = array();

    /**
     * Balise relative à l'élément.
     *
     * @var array
     */
    private $tags = array();

    /**
     * Tableau route.
     *
     * @var array
     */
    private $route = array();

    /**
     * Construction de l'élément du menu
     *
     * @param array - object $item 
     * @param array - object $items
     */
    public function __construct($item, &$items) {
        // Ajout des infos de l'item
        $this->data = $item;
        $this->addTags("li");

        // Enfant trouvé
        if ($item->parent_id > 0) {
            // Ajout de l'enfant
            $this->addAttributs("class", "item" . $item->menu_id);
            $items[$item->parent_id]->addChild($this);
        } else if ($item->parent_id == 0) {
            $this->addAttributs("class", "parent");
        }
    }

    /**
     * Ajoute un attribut à la liste.
     *
     * @param String $name nom de l'attribut
     * @param String $value valeur de l'attribut
     */
    public function addAttributs($name, $value) {
        if (!isset($this->attributs[$name])) {
            $this->attributs[$name] = $value;
        } else {
            // Conversion en tableau si besoin
            if (!is_array($this->attributs[$name])) {
                $firstValue = $this->attributs[$name];
                $this->attributs[$name] = array();
                $this->attributs[$name][] = $firstValue;
            }

            // Vérification des valeurs déjà enregistrées
            if (Exec_Utils::inArray($value, $this->attributs[$name]) == false) {
                if ($value == "parent") {
                    array_unshift($this->attributs[$name], $value);
                } else if ($value == "active") {
                    if ($this->attributs[$name][0] == "parent") {
                        // Remplace parent par active
                        $this->attributs[$name][0] = $value;
                        // Ajoute a nouveau parent en 1er
                        array_unshift($this->attributs[$name], "parent");
                    } else {
                        array_unshift($this->attributs[$name], $value);
                    }
                } else {
                    $this->attributs[$name][] = $value;
                }
            }
        }
    }

    /**
     * Supprime un attributs.
     *
     * @param String $name nom de l'attribut
     */
    public function removeAttributs($name = "") {
        if (!empty($name)) {
            unset($this->attributs[$name]);
        } else {
            foreach ($this->attributs as $key => $attributs) {
                unset($this->attributs[$key]);
            }
        }
    }

    /**
     * Mise en forme des attributs.
     *
     * @param array $attributs
     * @return String
     */
    public function &getAttributs(array $attributs = array()) {
        $rslt = "";
        $attributs = empty($attributs) ? $this->attributs : $attributs;

        foreach ($attributs as $attributsName => $value) {
            if (!is_int($attributsName)) {
                $rslt .= " " . $attributsName . "=\"";
            }

            if (is_array($value)) {
                $rslt .= $this->getAttributs($value);
            } else {
                if (!empty($rslt) && is_int($attributsName)) {
                    $rslt .= " ";
                }

                $rslt .= htmlspecialchars($value);
            }

            if (!is_int($attributsName)) {
                $rslt .= "\"";
            }
        }
        return $rslt;
    }

    /**
     * Ajout de balise tag pour l'élément.
     *
     * @param String $tag
     */
    public function addTags($tag) {
        $this->tags[] = $tag;
    }

    /**
     * Mise en place du tableau route.
     *
     * @param array $route
     */
    public function setRoute(array $route) {
        $this->route = $route;
    }

    /**
     * Ajoute un enfant à l'item courant.
     *
     * @param Libs_MenuElement or array - object $child
     * @param $items array - object
     */
    public function &addChild(&$child, &$items = array()) {
        // Création de l'enfant si besoin
        if (!is_object($child)) {
            $child = new Libs_MenuElement($child, $items);
        }

        // Ajout du tag UL si c'est un nouveau parent
        if (empty($this->child)) {
            $this->addTags("ul");
        }

        // Ajoute la classe parent
        $this->addAttributs("class", "parent");

        // Ajoute la classe élément
        if ($this->data->parent_id > 0) {
            $this->addAttributs("class", "item" . $this->data->menu_id);
        }

        $this->child[$child->data->menu_id] = &$child;
    }

    /**
     * Supprime un enfant.
     *
     * @param Libs_MenuElement or array - object $child
     * @param $items array - object
     */
    public function removeChild(&$child = null, &$items = array()) {
        if ($child === null) {
            foreach ($this->child as $key => $child) {
                unset($this->child[$key]);
            }
        } else {
            // TODO A REVOIR - transtypage dégoutant
            if (!is_object($child)) {
                $child = &$items[$child->menu_id];
            }
            unset($this->child[$child->data->menu_id]);
        }
    }

    /**
     * Retourne une représentation de la classe en chaine de caractères.
     *
     * @param String $callback
     */
    public function &toString($callback = "") {
        $text = $this->data->content;

        // Mise en forme du texte via la callback
        if (!empty($callback) && !empty($text)) {
            $text = Core_Loader::callback($callback, $text);
        }

        // Ajout de la classe active
        if (isset($this->route) && Exec_Utils::inArray($this->data->menu_id, $this->route)) {
            $this->addAttributs("class", "active");
            Libs_Breadcrumb::getInstance()->addTrail($text);
        }

        // Préparation des données
        $out = "";
        $end = "";
        $attributs = $this->getAttributs();
        $text = "<span>" . $text . "</span>";

        // Extraction des balises de débuts et de fin et ajout du texte
        foreach ($this->tags as $tag) {
            $out .= "<" . $tag . $attributs . ">" . $text;
            $text = "";
            $end = $end . "</" . $tag . ">";
        }

        // Constuction des branches
        if (!empty($this->child)) {
            foreach ($this->child as $child) {
                $child->route = $this->route;
                $out .= $child->toString($callback);
            }
        }

        // Ajout des balises de fin
        $out .= $end;
        return $out;
    }

    /**
     * Nettoyage à la destruction.
     */
    public function __destruct() {
        $this->item = array();
        $this->removeAttributs();
        $this->removeChild();
    }

}
