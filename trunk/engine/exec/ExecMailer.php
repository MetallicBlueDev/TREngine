<?php

namespace TREngine\Engine\Exec;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Gestionnaire de email
 *
 * @author Sébastien Villemain
 */
class ExecMailer {

    /**
     * Vérifie la validité du mail
     *
     * @param $address adresse email a vérifier
     * @return boolean true l'adresse email est valide
     */
    public static function validMail($address) {
        return (preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? true : false;
    }

    /**
     * Retourne une chaine ou une image généré
     *
     * @param $mail
     * @param $name
     * @return string or string with HTML TAG
     */
    public static function &protectedDisplay($mail, $name = "mail") {
        $mail = str_replace("@", "_AT_", $mail);
        $mail = str_replace(".", "_POINT_", $mail);
        $protected = "<a href=\"mailto:" . $mail . "\">" . $name . "</a>";

        // On utilise la librairie GD
        if (@extension_loaded('gd')) {
            return "<img src=\"engine/libs/imagegenerator.get.php?mode=text&amp;text=" . urlencode(base64_encode($mail)) . "\" alt=\"\" title=\"" . $name . "\" />";
        } else {
            return $protected;
        }
    }

    public static function sendMail() {

    }

}

?>