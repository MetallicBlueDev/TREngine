<?php
if (!defined("TR_ENGINE_INDEX")) {
    require("secure.class.php");
    new Core_Secure();
}

/**
 * traitement HTML.
 */
class Core_TextEditor {

    public static function smilies($text) {
        return $text;
    }

    public static function text($text) {
        return $text;
    }

}
