<?php

namespace TREngine\Blocks;

use TREngine\Engine\Core\CoreHtml;
use TREngine\Engine\Core\CoreCache;
use TREngine\Engine\Core\CoreTable;
use TREngine\Engine\Lib\LibMakeStyle;
use TREngine\Engine\Lib\LibMenu;
use TREngine\Engine\Exec\ExecEntities;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'engine' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Block de menu.
 *
 * @author Sébastien Villemain
 */
class BlockMenu extends BlockModel {

    /**
     * TAG pour les options
     *
     * @var string
     */
    private static $optionsTag = "__OPTIONS__";

    public function display() {
        $menus = $this->getMenu();

        $libMakeStyle = new LibMakeStyle();
        $libMakeStyle->assign("blockTitle", $this->getBlockData()->getTitle());
        $libMakeStyle->assign("blockContent", "<div class=\"menu\">" . $menus->render() . "</div>");
        $libMakeStyle->display($this->getBlockData()->getTemplateName());
    }

    protected function getMenu() {
        $menus = new LibMenu(
        "block" . $this->getBlockData()->getId(), array(
            "table" => CoreTable::MENUS_TABLES,
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
        CoreCache::getInstance(CoreCache::SECTION_MENUS)->removeCache("block" . $this->getBlockData()->getId() . ".php");
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
            $text = ExecEntities::textDisplay($matches[1]);

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
//                    $popup = 1;
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
                $text = CoreHtml::getLink($link, $text, false, "window.open('" . $link . "');return false;");
            }

            $outPut = $text;
        } else {
            // Aucun style appliquer
            // Conversion du texte
            $outPut = ExecEntities::textDisplay($line);
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
