<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("../engine/core/secure.class.php");
    new Core_Secure();
}

/**
 * Block de menu.
 *
 * @author Sébastien Villemain
 */
class Block_Menu extends Block_Model {

    /**
     * TAG pour les options
     *
     * @var String
     */
    private static $optionsTag = "__OPTIONS__";

    public function display() {
        $menus = $this->getMenu();

        $libsMakeStyle = new Libs_MakeStyle();
        $libsMakeStyle->assign("blockTitle", $this->title);
        $libsMakeStyle->assign("blockContent", "<div class=\"menu\">" . $menus->render() . "</div>");
        $libsMakeStyle->display($this->templateName);
    }

    protected function getMenu() {
        Core_Loader::classLoader("Libs_Menu");
        $menus = new Libs_Menu(
                "block" . $this->blockId, array(
            "table" => Core_Table::$MENUS_TABLES,
            "select" => array("menu_id", "block_id", "parent_id", "content", "sublevel", "position", "rank"),
            "where" => array("block_id = '" . $this->blockId . "'"),
            "orderby" => array("sublevel", "parent_id", "position"),
            "limit" => array()
                )
        );
        return $menus;
    }

    public function install() {

    }

    public function uninstall() {
        Core_CacheBuffer::setSectionName("menus");
        Core_CacheBuffer::removeCache("block" . $this->blockId . ".php");
    }

    /**
     * Retourne une ligne de menu propre sous forme HTML
     *
     * Exemple :
     * Link example__OPTIONS__B.I.U.A.?mod=home__OPTIONS__
     *
     * @param $line String
     * @return String
     */
    public static function getLine($line) {
        $outPut = "";
        $matches = null;

        if (preg_match("/(.+)" . self::$optionsTag . "(.*?)" . self::$optionsTag . "/", $line, $matches)) {
            // Conversion du texte
            $text = Exec_Entities::textDisplay($matches[1]);

            // Recherche des options et style
            $options = explode(".", $matches[2]);
            foreach ($options as $key => $value) {
                if ($value == "B") {
                    $bold = 1;
                }
                if ($value == "I") {
                    $italic = 1;
                }
                if ($value == "U") {
                    $underline = 1;
                }
                if ($value == "BIG") {
                    $big = 1;
                }
                if ($value == "SMALL") {
                    $small = 1;
                }
                if ($value == "A") {
                    $link = $options[$key + 1];
                }
                if ($value == "POPUP") {
                    $popup = 1;
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
                $link = Core_Html::getLink($link);
                $text = "<a href=\"" . (($popup) ? "javascript:window.open('" . $link . "')" : $link) . "\" alt=\"\" title=\"\">" . $text . "</a>";
            }

            $outPut = $text;
        } else {
            // Aucun style appliquer
            // Conversion du texte
            $outPut = Exec_Entities::textDisplay($line);
        }
        return $outPut;
    }

    /**
     * Retourne une ligne avec les TAGS
     *
     * @param $text String Texte du menu
     * @param $options array Options choisis
     * @return String
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
            $optionsString = self::$optionsTag . $optionsString . self::$optionsTag;
        }

        return $text . $optionsString;
    }

}

?>