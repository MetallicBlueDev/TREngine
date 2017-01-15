<?php

namespace TREngine\Engine\Exec;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Outil de manipulation des images.
 *
 * @author Sébastien Villemain
 */
class ExecImage {

    /**
     * Type d'image autorisé.
     *
     * @var array
     */
    const TYPE_LIST = array(
        "IMAGETYPE_GIF",
        "IMAGETYPE_JPEG",
        "IMAGETYPE_JPG",
        "IMAGETYPE_PNG",
        "IMAGETYPE_BMP",
    );

    /**
     * Retourne la balise complète de l'image avec redimension.
     *
     * @param string $url
     * @param int $widthDefault
     * @param int $heightDefault
     * @return string
     */
    public static function &getTag(string $url, int $widthDefault = 350, int $heightDefault = -1): string {
        $img = "";

        $infos = self::getInfos($url);

        if (!empty($infos)) {
            $width = $infos[0];
            $height = $infos[1];

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

                $img = "<img src=\"" . $url . "\" width=\"" . $width . "\" height=\"" . $height . "\" alt=\"\" style=\"border: 0;\" />";
            }

            if (empty($img) && $heightDefault > 0) {
                $img = "<img src=\"" . $url . "\" width=\"" . $widthDefault . "\" height=\"" . $heightDefault . "\" alt=\"\" style=\"border: 0;\" />";
            } else if (empty($img)) {
                $img = "<img src=\"" . $url . "\" width=\"" . $widthDefault . "\" alt=\"\" style=\"border: 0;\" />";
            }
        }
        return $img;
    }

    /**
     * Retourne un tableau contenant les informations sur l'image.
     * Si l'image n'est pas valide, retourne un tableau vide.
     *
     * @param string $url
     * @return array
     */
    public static function getInfos(string $url): array {
        $type = array();

        if (is_file($url)) {
            $infos = getimagesize($url);

            if ($infos !== false && isset($infos[2]) && ExecUtils::inArray($infos[2], self::TYPE_LIST, true)) {
                $type[] = isset($infos[0]) ? $infos[0] : 0;
                $type[] = isset($infos[1]) ? $infos[1] : 0;
                $type[] = $infos[2];
            }
        }
        return $type;
    }

}
