<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * traitement HTML.
 */
class Core_TextEditor {

    public static function &smilies($text) {
        return $text;
    }

    public static function &text($text) {
        return $text;
    }

}
