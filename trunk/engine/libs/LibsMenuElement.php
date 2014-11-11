<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Membre d'un menu.
 *
 * @author Sébastien Villemain
 */
class LibsMenuElement extends CoreDataStorage {

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
     * @param array $item
     * @param array $items
     */
    public function __construct(array $item, array &$items) {
        parent::__construct();

        $this->newStorage($item);
        $this->addTags("li");

        // Enfant trouvé
        if ($this->getParentId() > 0) {
            // Ajout de l'enfant
            $this->addAttributs("class", "item" . $this->getMenuId());
            $items[$this->getParentId()]->addChild($this);
        } else if ($this->getParentId() == 0) {
            $this->addAttributs("class", "parent");
        }
    }

    /**
     * Retourne l'identifiant du menu parent.
     *
     * @return int
     */
    public function &getParentId() {
        return $this->getDataValue("parent_id");
    }

    /**
     * Retourne l'identifiant du menu.
     *
     * @return int
     */
    public function &getMenuId() {
        return $this->getDataValue("menu_id");
    }

    /**
     * Retourne le contenu du menu.
     *
     * @return string
     */
    public function &getContent() {
        return $this->getDataValue("content");
    }

    /**
     * Retourne le rang d'accès du menu.
     *
     * @return int
     */
    public function &getRank() {
        return $this->getDataValue("rank");
    }

    /**
     * Ajoute un attribut à la liste.
     *
     * @param string $name nom de l'attribut
     * @param string $value valeur de l'attribut
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
            if (!Exec_Utils::inArray($value, $this->attributs[$name])) {
                if ($value === "parent") {
                    array_unshift($this->attributs[$name], $value);
                } else if ($value === "active") {
                    if ($this->attributs[$name][0] === "parent") {
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
     * @param string $name nom de l'attribut
     */
    public function removeAttributs($name = "") {
        if (!empty($name)) {
            unset($this->attributs[$name]);
        } else {
            foreach (array_keys($this->attributs) as $key) {
                unset($this->attributs[$key]);
            }
        }
    }

    /**
     * Mise en forme des attributs.
     *
     * @param array $attributs
     * @return string
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
     * @param string $tag
     */
    public function addTags($tag) {
        $this->tags[] = $tag;
    }

    /**
     * Retourne le tableau de routage.
     *
     * @return array
     */
    public function &getRoute() {
        return $this->route;
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
     * @param LibsMenuElement  $child
     */
    public function addChild(LibsMenuElement &$child) {
        // Ajout du tag UL si c'est un nouveau parent
        if (empty($this->child)) {
            $this->addTags("ul");
        }

        // Ajoute la classe parent
        $this->addAttributs("class", "parent");

        // Ajoute la classe élément
        if ($this->getParentId() > 0) {
            $this->addAttributs("class", "item" . $this->getMenuId());
        }

        $this->child[$child->getMenuId()] = &$child;
    }

    /**
     * Supprime un enfant.
     *
     * @param LibsMenuElement $child
     */
    public function removeChild(LibsMenuElement &$child = null) {
        if ($child === null) {
            foreach (array_keys($this->child) as $key) {
                unset($this->child[$key]);
            }
        } else {
            unset($this->child[$child->getMenuId()]);
        }
    }

    /**
     * Retourne une représentation de la classe en chaine de caractères.
     *
     * @param string $callback
     */
    public function &toString($callback = "") {
        $text = $this->getContent();

        // Mise en forme du texte via la callback
        if (!empty($callback) && !empty($text)) {
            $text = CoreLoader::callback($callback, $text);
        }

        // Ajout de la classe active
        if (isset($this->route) && Exec_Utils::inArray($this->getMenuId(), $this->route)) {
            $this->addAttributs("class", "active");
            LibsBreadcrumb::getInstance()->addTrail($text);
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
