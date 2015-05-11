<?php

namespace TREngine\Engine\Exec;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Outil de manipulation des emails.
 *
 * @author Sébastien Villemain
 */
class ExecMailer {

    /**
     * Vérifie la validité du mail.
     *
     * @param string $address L'adresse email à vérifier
     * @return boolean true L'adresse email est valide
     */
    public static function validMail($address) {
        return (preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? true : false;
    }

    /**
     * Retourne une chaine ou une image généré
     *
     * @param string $mail
     * @param string $name
     * @return string
     */
    public static function &protectedDisplay($mail, $name = "mail") {
        $rslt = null;
        $mail = str_replace("@", "_AT_", $mail);
        $mail = str_replace(".", "_DOT_", $mail);
        $protected = "<a href=\"mailto:" . $mail . "\">" . $name . "</a>";

        if (extension_loaded('gd')) {
            $rslt = "<img src=\"index.php?layout=block&amp;blockType=ImageGenerator&amp;mode=text&amp;text=" . urlencode(base64_encode($mail)) . "\" alt=\"\" title=\"" . $name . "\" />";
        } else {
            $rslt = $protected;
        }
        return $rslt;
    }

    public static function sendMail() {

    }

}

?>