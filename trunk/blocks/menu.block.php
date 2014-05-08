<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "engine" . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Block de menu.
 *
 * @author Sébastien Villemain
 */
class Block_Menu extends Libs_BlockModel {

    /**
     * TAG pour les options
     *
     * @var string
     */
    private static $optionsTag = "__OPTIONS__";

    public function display() {
        $menus = $this->getMenu();

        $libsMakeStyle = new Libs_MakeStyle();
        $libsMakeStyle->assign("blockTitle", $this->getBlockData()->getTitle());
        $libsMakeStyle->assign("blockContent", "<div class=\"menu\">" . $menus->render() . "</div>");
        $libsMakeStyle->display($this->getBlockData()->getTemplateName());
    }

    protected function getMenu() {
        $menus = new Libs_Menu(
        "block" . $this->getBlockData()->getId(), array(
            "table" => Core_Table::MENUS_TABLES,
            "select" => array(
                "menu_id",
                "block_id",
                "parent_id",
                "content",
                "sublevel",
                "position",
                "rank"),
            "where" => array(
                "block_id = '" . $this->getBlockData()->getId() . "'"),
            "orderby" => array(
                "sublevel",
                "parent_id",
                "position"),
            "limit" => array()
        )
        );
        return $menus;
    }

    public function install() {

    }

    public function uninstall() {
        Core_Cache::changeCurrentSection(Core_Cache::SECTION_MENUS);
        Core_Cache::removeCache("block" . $this->getBlockData()->getId() . ".php");
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
                $text = Core_Html::getLink($link, $text, false, "window.open('" . $link . "');return false;");
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
            $optionsString = self::$optionsTag . $optionsString . self::$optionsTag;
        }

        return $text . $optionsString;
    }

}

?>