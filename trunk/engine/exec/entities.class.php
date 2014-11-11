<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Convertiseur de chaine de caractère en entities
 *
 * @author Sébastien Villemain
 */
class Exec_Entities {

    /**
     * Transforme une chaine non-encodée, et la convertit en entitiées unicode &#xxx;
     * pour que ça s'affiche correctement dans les navigateurs.
     *
     * @param string $source : la chaine
     * @return string $encodedString : chaine et ses entitées
     */
    public static function &entitiesUtf8($source) {
        // Remplace les entités numériques
//        $source = preg_replace_callback(
//        '~&#x([0-9a-f]+);~i', function($m) {
//            return "chr(hexdec($m[1]))";
//        }, $source);
//
//        $source = preg_replace_callback(
//        '~&#([0-9]+);~', function($m) {
//            return "chr($m[1])";
//        }, $source);
        return $source;
//        // Remplace les entités litérales
//        $trans_tbl = get_html_translation_table(HTML_ENTITIES);
//        $trans_tbl = array_flip($trans_tbl);
//        $source = strtr($source, $trans_tbl);
//
//        // Entitées UTF-8
//        $source = utf8_encode($source);
//
//        // array used to figure what number to decrement from character order value
//        // according to number of characters used to map unicode to ascii by utf-8
//        $decrement[4] = 240;
//        $decrement[3] = 224;
//        $decrement[2] = 192;
//        $decrement[1] = 0;
//
//        // the number of bits to shift each charNum by
//        $shift[1][0] = 0;
//        $shift[2][0] = 6;
//        $shift[2][1] = 0;
//        $shift[3][0] = 12;
//        $shift[3][1] = 6;
//
//        $shift[3][2] = 0;
//        $shift[4][0] = 18;
//        $shift[4][1] = 12;
//        $shift[4][2] = 6;
//        $shift[4][3] = 0;
//
//        $pos = 0;
//        $len = strlen($source);
//        $encodedString = '';
//        while ($pos < $len) {
//            $charPos = substr($source, $pos, 1);
//            $asciiPos = ord($charPos);
//
//            if ($asciiPos < 128) {
//                $encodedString .= htmlentities($charPos);
//                $pos++;
//                continue;
//            }
//
//            if (($asciiPos >= 240) && ($asciiPos <= 255)) {
//                $i = 4;
//            } // 4 chars representing one unicode character
//            else if (($asciiPos >= 224) && ($asciiPos <= 239)) {
//                $i = 3;
//            } // 3 chars representing one unicode character
//            else if (($asciiPos >= 192) && ($asciiPos <= 223)) {
//                $i = 2;
//            } // 2 chars representing one unicode character
//            else {
//                $i = 1;
//            } // 1 char (lower ascii)
//
//            $thisLetter = substr($source, $pos, $i);
//            $pos += $i;
//
//            // process the string representing the letter to a unicode entity
//            $thisLen = strlen($thisLetter);
//            $decimalCode = 0;
//
//            for ($thisPos = 0; $thisPos < $thisLen; $thisPos++) {
//                $thisCharOrd = ord(substr($thisLetter, $thisPos, 1));
//
//                if ($thisPos == 0) {
//                    $charNum = intval($thisCharOrd - $decrement[$thisLen]);
//                    $decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
//                } else {
//                    $charNum = intval($thisCharOrd - 128);
//                    $decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
//                }
//            }
//
//            $encodedLetter = '&#' . str_pad($decimalCode, ($thisLen == 1) ? 3 : 5, '0', STR_PAD_LEFT) . ';';
//            $encodedString .= $encodedLetter;
//        }
//        $source = utf8_encode($source);
//        return $encodedString;
    }

    /**
     * Ajout de slashes dans le texte
     *
     * @param $text string
     * @return string
     */
    public static function &addSlashes($text) {
        $text = addslashes($text);
        return $text;
    }

    /**
     * Supprime les slashes ajouté par addSlashes
     *
     * @param $text
     * @return string
     */
    public static function &stripSlashes($text) {
        $text = stripslashes($text);
        return $text;
    }

    /**
     * Prépare le texte pour un affichage
     *
     * @param $text
     * @return string
     */
    public static function &textDisplay($text) {
        $text = self::entitiesUtf8($text);
        //$text = self::stripSlashes($text);
        if (CoreLoader::isCallable("CoreTextEditor")) {
            $text = CoreTextEditor::text($text);
            $text = CoreTextEditor::smilies($text);
        }
        $text = self::secureText($text);
        return $text;
    }

    /**
     * Sécurise le texte
     *
     * @param $string
     * @return string
     */
    public static function &secureText($string) {
        $secure = array(
            "content-disposition:" => "&#99;&#111;&#110;&#116;&#101;&#110;&#116;&#45;&#100;&#105;&#115;&#112;&#111;&#115;&#105;&#116;&#105;&#111;&#110;&#58;",
            "content-type:" => "&#99;&#111;&#110;&#116;&#101;&#110;&#116;&#45;&#116;&#121;&#112;&#101;&#58;",
            "content-transfer-encoding:" => "&#99;&#111;&#110;&#116;&#101;&#110;&#116;&#45;&#116;&#114;&#97;&#110;&#115;&#102;&#101;&#114;&#45;&#101;&#110;&#99;&#111;&#100;&#105;&#110;&#103;&#58;",
            "include" => "&#105;&#110;&#99;&#108;&#117;&#100;&#101;",
            "include_once" => "&#105;&#110;&#99;&#108;&#117;&#100;&#101;&#95;&#111;&#110;&#99;&#101;",
            "require" => "&#114;&#101;&#113;&#117;&#105;&#114;&#101;",
            "require_once" => "&#114;&#101;&#113;&#117;&#105;&#114;&#101;&#95;&#111;&#110;&#99;&#101;",
            "\<\?" => "&lt;?",
            "<\?php" => "&lt;?php",
            "\?\>" => "?&gt;",
            "script" => "&#115;&#99;&#114;&#105;&#112;&#116;",
            "eval" => "&#101;&#118;&#97;&#108;",
            "javascript" => "&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;",
            "embed" => "&#101;&#109;&#98;&#101;&#100;",
            "iframe" => "&#105;&#102;&#114;&#97;&#109;&#101;",
            "refresh" => "&#114;&#102;&#114;&#101;&#115;&#104;",
            "onload" => "&#111;&#110;&#108;&#111;&#97;&#100;",
            "onstart" => "&#111;&#110;&#115;&#116;&#97;&#114;&#116;",
            "onerror" => "&#111;&#110;&#101;&#114;&#114;&#111;&#114;",
            "onabort" => "&#111;&#110;&#97;&#98;&#111;&#114;&#116;",
            "onblur" => "&#111;&#110;&#98;&#108;&#117;&#114;",
            "onchange" => "&#111;&#110;&#99;&#104;&#97;&#110;&#103;&#101;",
            "onclick" => "&#111;&#110;&#99;&#108;&#105;&#99;&#107;",
            "ondblclick" => "&#111;&#110;&#100;&#98;&#108;&#99;&#108;&#105;&#99;&#107;",
            "onfocus" => "&#111;&#110;&#102;&#111;&#99;&#117;&#115;",
            "onkeydown" => "&#111;&#110;&#107;&#101;&#121;&#100;&#111;&#119;&#110;",
            "onkeypress" => "&#111;&#110;&#107;&#101;&#121;&#112;&#114;&#101;&#115;&#115;",
            "onkeyup" => "&#111;&#110;&#107;&#101;&#121;&#117;&#112;",
            "onmousedown" => "&#111;&#110;&#109;&#111;&#117;&#115;&#101;&#100;&#111;&#119;&#110;",
            "onmousemove" => "&#111;&#110;&#109;&#111;&#117;&#115;&#101;&#109;&#111;&#118;&#101;",
            "onmouseover" => "&#111;&#110;&#109;&#111;&#117;&#115;&#101;&#111;&#118;&#101;&#114;",
            "onmouseout" => "&#111;&#110;&#109;&#111;&#117;&#115;&#101;&#111;&#117;&#116;",
            "onmouseup" => "&#111;&#110;&#109;&#111;&#117;&#115;&#101;&#117;&#112;",
            "onreset" => "&#111;&#110;&#114;&#101;&#115;&#101;&#116;",
            "onselect" => "&#111;&#110;&#115;&#101;&#108;&#101;&#99;&#116;",
            "onsubmit" => "&#111;&#110;&#115;&#117;&#98;&#109;&#105;&#116;",
            "onunload" => "&#111;&#110;&#117;&#110;&#108;&#111;&#97;&#100;",
            "document" => "&#100;&#111;&#99;&#117;&#109;&#101;&#110;&#116;",
            "cookie" => "&#99;&#111;&#111;&#107;&#105;&#101;",
            "vbscript" => "&#118;&#98;&#115;&#99;&#114;&#105;&#112;&#116;",
            "location" => "&#108;&#111;&#99;&#97;&#116;&#105;&#111;&#110;",
            "object" => "&#111;&#98;&#106;&#101;&#99;&#116;",
            "vbs" => "&#118;&#98;&#115;",
            "href" => "&#104;&#114;&#101;&#102;",
            "define" => "&#100;&#101;&#102;&#105;&#110;&#101;"
        );

        foreach ($secure as $search => $replace) {
            $string = str_ireplace($search, $replace, $string);
        }
        return $string;
    }

}

?>