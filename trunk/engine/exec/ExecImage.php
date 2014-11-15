<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Outil de cryptage
 *
 * @author Sébastien Villemain
 */
class ExecImage {

    /**
     * Type d'image autorisé
     *
     * @var array
     */
    private static $allowed = array(
        "IMAGETYPE_GIF",
        "IMAGETYPE_JPEG",
        "IMAGETYPE_JPG",
        "IMAGETYPE_PNG",
        "IMAGETYPE_BMP",
    );

    /**
     * Buffer de largeur
     *
     * @var array
     */
    public static $width = array();

    /**
     * Buffer de hauteur
     *
     * @var array
     */
    public static $height = array();

    /**
     * Buffer de type
     *
     * @var array
     */
    public static $type = array();

    /**
     * Redimension une image
     *
     * @param $url string
     * @param $widthDefault int
     * @param $heightDefault int
     * @return string balise img complete
     */
    public static function &resize($url, $widthDefault = 350, $heightDefault = "") {
        $img = "";
        if (self::isValid($url)) {
            // Reset des variables
            $width = "";
            $height = "";

            // Recherche dans le buffer
            $key = self::getKey($url);
            if (isset(self::$width[$key]) && isset(self::$height[$key])) {
                $width = self::$width[$key];
                $height = self::$height[$key];
            } else {
                if ((list($widthImg, $heightImg) = @getimagesize($url)) !== false) {
                    self::$width[$key] = $width;
                    self::$height[$key] = $height;
                    $width = $widthImg;
                    $height = $heightImg;
                }
            }

            // Redimension si possible
            if ($width && $height) {
                foreach (array(
                    'width',
                    'height') as $original) {
                    $default = $original . "Default";

                    if (${$original} > ${$default} && ${$default}) {
                        $originalInverse = ($original == 'width') ? 'height' : 'width';
                        $resize = ${$default} / ${$original};
                        ${$original} = ${$default};
                        ${$originalInverse} = ceil(${$originalInverse} * $resize);
                    }
                }
                $img = "<img src=\"" . $url . "\" width=\"" . $width . "\" height=\"" . $height . "\" alt=\"\" style=\"border: 0;\" />";
            }
            // Si aucune redimension réalisable
            if (empty($img) && $heightDefault) {
                $img = "<img src=\"" . $url . "\" width=\"" . $widthDefault . "\" height=\"" . $heightDefault . "\" alt=\"\" style=\"border: 0;\" />";
            } else if (empty($img)) {
                $img = "<img src=\"" . $url . "\" width=\"" . $widthDefault . "\" alt=\"\" style=\"border: 0;\" />";
            }
        }
        return $img;
    }

    /**
     * Vérifie si l'image est valide
     *
     * @param $url string
     * @return boolean true image valide
     */
    public static function &isValid($url) {
        if (is_file($url)) {
            $type = self::getType($url);
            if (isset(self::$allowed[$type])) {
                $ext = strrchr($url, ".");
                $ext = substr($ext, 1);
                $ext = "IMAGETYPE_" . strtoupper($ext);
                if (self::$allowed[$type] == $ext) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Retourne le type de l'image
     *
     * @param $url string
     * @return Constante IMAGETYPE_xxx
     */
    public static function &getType($url) {
        $key = self::getKey($url);
        if (isset(self::$type[$key])) {
            $type = self::$type[$key];
        } else {
            // Fonction de substitution pour exif_imagetype
            if (!function_exists("exif_imagetype")) {

                function exif_imagetype($filename) {
                    if ((list($width, $height, $type, $attr) = getimagesize($filename)) !== false) {
                        $key = ExecImage::getKey($url);
                        ExecImage::$width[$key] = $width;
                        ExecImage::$height[$key] = $height;
                        ExecImage::$type[$key] = $type;
                        return $type;
                    }
                }

            }
            $type = exif_imagetype($url);
            self::$type[$key] = $type;
        }
        return $type;
    }

    /**
     * Retourne la clé codé pour le buffer
     *
     * @param $url string
     * @return string
     */
    public static function &getKey($url) {
        return urlencode($url);
    }

}

?>