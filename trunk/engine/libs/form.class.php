<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Classe de mise en forme d'un formulaire.
 * Avec mise en cache automatique.
 *
 * @author Sébastien Villemain
 */
class Libs_Form {

    /**
     * Nom du formulaire.
     *
     * @var string
     */
    private $name = "";

    /**
     * Url pour le tag d'action.
     *
     * @var string
     */
    private $urlAction = "";

    /**
     * Titre du formulaire.
     *
     * @var string
     */
    private $title = "";

    /**
     * Description du formulaire.
     *
     * @var string
     */
    private $description = "";

    /**
     * Donnée contenu dans le formulaire.
     *
     * @var string
     */
    private $inputData = "";

    /**
     * Fermeture de la balise fieldset.
     *
     * @var boolean
     */
    private $doFieldset = true;

    /**
     * Formulaire présent dans le cache.
     *
     * @var boolean
     */
    private $cached = false;

    /**
     * Variable mise en cache.
     *
     * @var array
     */
    private $cacheVars = array();

    /**
     * Position dans le tableau des variables cache.
     *
     * @var int
     */
    private $cacheVarsIndex = 0;

    /**
     * Nouveau formulaire.
     *
     * @param string $name identifiant du formlaire : attention à ne pas prendre un nom déjà utilisé !
     * @param string $urlAction
     */
    public function __construct($name, $urlAction = "") {
        $this->name = $name;
        $this->urlAction = !empty($urlAction) ? $urlAction : "index.php";

        Core_CacheBuffer::changeCurrentSection(Core_CacheBuffer::SECTION_FORMS);
        $this->cached = Core_CacheBuffer::cached($name . ".php");
    }

    /**
     * Affecte un title au début du formulaire.
     *
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * Affecte une description au début du formulaire.
     *
     * @param string $description
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * Ajoute un fieldset.
     *
     * @param string $title
     * @param string $description
     */
    public function addFieldset($title = "", $description = "") {
        $title = $this->getTitle($title);
        $description = $this->getDescription($description);

        if (!$this->cached) {
            if (!$this->doFieldset) {
                $this->doFieldset = true;
            } else {
                $this->inputData .= "</fieldset>";
            }

            $this->inputData .= "<fieldset>"
            . $title . $description;
        }
    }

    /**
     * Ajoute un champs de type texte.
     *
     * @param string $name
     * @param string $description
     * @param string $defaultValue
     * @param string $options
     * @param string $class
     */
    public function addInputText($name, $description = "", $defaultValue = "", $options = "", $class = "") {
        $this->addInput($name, $name, $description, "text", $defaultValue, $options, $class);
    }

    /**
     * Ajoute un champs masqué.
     *
     * @param string $name
     * @param string $defaultValue
     * @param string $options
     */
    public function addInputHidden($name, $defaultValue, $options = "") {
        $this->addInput($name, $name, "", "hidden", $defaultValue, $options, "");
    }

    /**
     * Ajoute un bouton d'envoi.
     *
     * @param string $name
     * @param string $defaultValue
     * @param string $options
     * @param string $class
     */
    public function addInputSubmit($name, $defaultValue, $options = "", $class = "") {
        $this->addInput($name, $name, "", "submit", $defaultValue, $options, $class);
    }

    /**
     * Ajoute un champs de type bouton radio.
     *
     * @param string $id
     * @param string $name
     * @param string $description
     * @param boolean $checked
     * @param string $defaultValue
     * @param string $options
     * @param string $class
     */
    public function addInputRadio($id, $name, $description = "", $checked = false, $defaultValue = "", $options = "", $class = "") {
        if (empty($class)) {
            $class = "radio";
        }

        if ($checked) {
            $options = "checked=\"checked\"" . ((!empty($options)) ? " " . $options : "");
        }

        $this->addInput($id, $name, $description, "radio", $defaultValue, $options, $class);
    }

    /**
     * Ajoute un champs de type case à cocher.
     *
     * @param string $id
     * @param string $name
     * @param string $description
     * @param boolean $checked
     * @param string $defaultValue
     * @param string $options
     * @param string $class
     */
    public function addInputCheckbox($id, $name, $description = "", $checked = false, $defaultValue = "", $options = "", $class = "") {
        if (empty($class)) {
            $class = "checkbox";
        }

        if ($checked) {
            $options = "checked=\"checked\"" . ((!empty($options)) ? " " . $options : "");
        }

        $this->addInput($id, $name, $description, "checkbox", $defaultValue, $options, $class);
    }

    /**
     * Ajoute un bouton.
     *
     * @param string $name
     * @param string $description
     * @param string $defaultValue
     * @param string $options
     * @param string $class
     */
    public function addInputButton($name, $description = "", $defaultValue = "", $options = "", $class = "") {
        $this->addInput($name, $name, $description, "button", $defaultValue, $options, $class);
    }

    /**
     * Ajoute un champs de type mot de passe.
     *
     * @param string $name
     * @param string $description
     * @param string $options
     * @param string $class
     */
    public function addInputPassword($name, $description = "", $options = "", $class = "") {
        $this->addInput($name, $name, $description, "password", "", $options, $class);
    }

    /**
     * Ajoute un textarea.
     *
     * @param string $name
     * @param string $description
     * @param string $defaultValue
     * @param string $options
     * @param string $class
     */
    public function addTextarea($name, $description, $defaultValue = "", $options = "", $class = "") {
        if (empty($class)) {
            $class = "textarea";
        }

        $idDescription = $this->getId($name);
        $description = $this->getLabel($name, $description);
        $id = $this->getId($name, "input");

        $this->addCacheVar($name);
        $name = $this->getLastCacheVar();

        $this->addCacheVar($class);
        $class = $this->getLastCacheVar();

        $this->addCacheVar($options);
        $options = $this->getLastCacheVar();

        $defaultValue = Exec_Entities::stripSlashes($defaultValue);
        $this->addCacheVar($defaultValue);
        $defaultValue = $this->getLastCacheVar();

        if (!$this->cached) {
            $this->inputData .= "<p id=\"" . $idDescription . "\">" . $description
            . " <textarea id=\"" . $id . "\" name=\"" . $name . "\""
            . " class=\"" . $class . "\" " . $options . " >"
            . $defaultValue . "</textarea></p>";
        }
    }

    /**
     * Ajoute un liste déroulante (balise ouvrante seulement).
     *
     * @param string $name
     * @param string $description
     * @param string $options
     * @param string $class
     */
    public function addSelectOpenTag($name, $description, $options = "", $class = "") {
        if (empty($class)) {
            $class = "select";
        }

        $idDescription = $this->getId($name);
        $description = $this->getLabel($name, $description);
        $id = $this->getId($name, "input");

        $this->addCacheVar($name);
        $name = $this->getLastCacheVar();

        $this->addCacheVar($class);
        $class = $this->getLastCacheVar();

        $this->addCacheVar($options);
        $options = $this->getLastCacheVar();

        if (!$this->cached) {
            $this->inputData .= "<p id=\"" . $idDescription . "\">" . $description
            . " <select id=\"" . $id . "\" name=\"" . $name . "\""
            . " class=\"" . $class . "\" " . $options . ">";
        }
    }

    /**
     * Ajoute un élément dans la liste déroulante ouverte.
     *
     * @param string $value
     * @param string $description
     * @param boolean $selected
     * @param string $options
     */
    public function addSelectItemTag($value, $description = "", $selected = false, $options = "") {
        if ($selected) {
            $options = "selected=\"selected\"" . ((!empty($options)) ? " " . $options : "");
        }

        $description = ((!empty($description)) ? Exec_Entities::textDisplay($description) : $value);

        $this->addCacheVar($value);
        $value = $this->getLastCacheVar();

        $this->addCacheVar($options);
        $options = $this->getLastCacheVar();

        $this->addCacheVar($description);
        $description = $this->getLastCacheVar();

        if (!$this->cached) {
            $this->inputData .= " <option value=\"" . $value . "\" " . $options . ">"
            . $description . "</option>";
        }
    }

    /**
     * Ajoute la fermeture de la liste déroulante.
     */
    public function addSelectCloseTag() {
        if (!$this->cached) {
            $this->inputData .= "</select></p>";
        }
    }

    /**
     * Ajoute du code HTML en plus dans le fieldset courant.
     *
     * @param string $html
     */
    public function addHtmlInFieldset($html) {
        $this->addCacheVar($html);

        if (!$this->cached) {
            $this->inputData .= "<p>" . $this->getLastCacheVar() . "</p>";
        }
    }

    /**
     * Ajoute un espace.
     */
    public function addSpace() {
        if (!$this->cached) {
            $this->inputData .= "<p><br /></p>";
        }
    }

    /**
     * Ajoute du code HTML en plus après avoir fermé le fieldset courant.
     *
     * @param string $html
     */
    public function addHtmlOutFieldset($html) {
        $this->addCacheVar($html);

        if (!$this->cached) {
            if ($this->doFieldset) {
                $this->inputData .= "</fieldset>";
                $this->doFieldset = false;
            }

            $this->inputData .= "<p>" . $this->getLastCacheVar() . "</p>";
        }
    }

    /**
     * Retourne le rendu du formulaire complet.
     * Attention, ceci procédera a une sauvegarde et une lecture du cache.
     *
     * @param string $class
     * @return string
     */
    public function &render($class = "") {
        if (empty($class)) {
            $class = "form";
        }

        $this->addCacheVar($this->urlAction);
        $url = $this->getLastCacheVar();

        $this->addCacheVar($this->name);
        $name = $this->getLastCacheVar();

        $this->addCacheVar($class);
        $class = $this->getLastCacheVar();

        $title = $this->getTitle($this->title);
        $description = $this->getDescription($this->description);

        Core_CacheBuffer::changeCurrentSection(Core_CacheBuffer::SECTION_FORMS);
        $content = "";

        if ($this->cached) { // Récupèration des données mise en cache
            $content = Core_CacheBuffer::getCache($this->name . ".php", $this->cacheVars);
        } else { // Préparation puis mise en cache
            $data = "<form action=\"" . $url . "\" method=\"post\" id=\"form-" . $name . "\" name=\"" . $name . "\""
            . " class=\"" . $class . "\"><fieldset>" . $title . $description . $this->inputData
            . (($this->doFieldset) ? "</fieldset>" : "") . "</form>";

            // Enregistrement dans le cache
            $data = Core_CacheBuffer::serializeData($data);
            Core_CacheBuffer::writingCache($this->name . ".php", $data);

            // Lecture pour l'affichage
            eval(" \$content = $data; "); // Ne pas ajouter de quote : les données sont déjà serialisées
        }
        return $content;
    }

    /**
     * Retourne le code HTML pour le label.
     *
     * @param string $name
     * @param string $description
     * @return string
     */
    private function &getLabel($name, $description) {
        $id = $this->getId($name, "input");
        $description = Exec_Entities::textDisplay($description);
        $this->addCacheVar($description);

        $rslt = "";

        if (!$this->cached) {
            $rslt = "<label for=\"" . $id . "\">" . $this->getLastCacheVar() . "</label>";
        }
        return $rslt;
    }

    /**
     * Retourne le code HTML pour le titre.
     *
     * @param string $title
     * @return string
     */
    private function &getTitle($title) {
        $title = Exec_Entities::textDisplay($title);
        $this->addCacheVar($title);

        $rslt = "";

        if (!$this->cached) {
            $rslt = "<legend><b>" . $this->getLastCacheVar() . "</b></legend>";
        }
        return $rslt;
    }

    /**
     * Retourne le code HTML pour la description.
     *
     * @param string $description
     * @return string
     */
    private function &getDescription($description) {
        $id = $this->getId("description");
        $description = Exec_Entities::textDisplay($description);
        $this->addCacheVar($description);

        $rslt = "";

        if (!$this->cached) {
            $rslt = "<p class=\"" . $id . "\">" . $this->getLastCacheVar() . "</p>";
        }
        return $rslt;
    }

    /**
     * Retourne l'identifiant du champs.
     *
     * @param string $name
     * @param string $options
     * @return string
     */
    private function &getId($name, $options = "") {
        if (!empty($options)) {
            $options = "-" . $options;
        }

        $id = "form-" . $this->name . "-" . $name . $options;
        $this->addCacheVar($id);

        $rslt = "";

        if (!$this->cached) {
            $rslt = $this->getLastCacheVar();
        }
        return $rslt;
    }

    /**
     * Ajoute un champs.
     *
     * @param string $id
     * @param string $name
     * @param string $description
     * @param string $type
     * @param string $defaultValue
     * @param string $options
     * @param string $class
     */
    private function addInput($id, $name, $description, $type, $defaultValue = "", $options = "", $class = "") {
        if (empty($class)) {
            $class = "input";
        }

        $idDescription = $this->getId($name);
        $description = $this->getLabel($id, $description);
        $id = $this->getId($id, "input");

        $this->addCacheVar($name);
        $name = $this->getLastCacheVar();

        $this->addCacheVar($type);
        $type = $this->getLastCacheVar();

        $this->addCacheVar($class);
        $class = $this->getLastCacheVar();

        $this->addCacheVar($defaultValue);
        $defaultValue = $this->getLastCacheVar();

        $this->addCacheVar($options);
        $options = $this->getLastCacheVar();

        if (!$this->cached) {
            $this->inputData .= "<p id=\"" . $idDescription . "\">" . $description
            . " <input id=\"" . $id . "\" name=\"" . $name . "\" type=\"" . $type . "\""
            . " class=\"" . $class . "\" value=\"" . $defaultValue . "\" " . $options . " />"
            . "</p>";
        }
    }

    /**
     * Ajoute d'une valeur en cache.
     *
     * @param string $value
     */
    private function addCacheVar($value) {
        $this->cacheVars[$this->cacheVarsIndex++] = $value;
    }

    /**
     * Retourne la dernière variable de cache.
     *
     * @see Core_CacheBuffer::getCache()
     * @return string
     */
    private function &getLastCacheVar() {
        $rslt = "";

        if (!$this->cached) {
            // Le nom de la "vars" est relatif a Core_CacheBuffer::getCache()
            $rslt = "$" . "vars[" . ($this->cacheVarsIndex - 1) . "]";
        }
        return $rslt;
    }

}
