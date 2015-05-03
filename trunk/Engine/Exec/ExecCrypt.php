<?php

namespace TREngine\Engine\Exec;

use TREngine\Engine\Core\CoreLoader;
use TREngine\Engine\Core\CoreLogger;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Outil de manipulation de données pour le cryptage.
 *
 * @author Sébastien Villemain
 */
class ExecCrypt {

    /**
     * Création d'une clé unique.
     *
     * @param int $size
     * @return string
     */
    public static function &makeNewKey($size = 32, $letter = true, $number = true, $caseSensitive = true) {
        $randKey = "";
        $string = "";
        $letters = "abcdefghijklmnopqrstuvwxyz";
        $numbers = "0123456789";

        if ($letter && $number) {
            $string = $letters . $numbers;
        } else if ($letter) {
            $string = $letters;
        } else {
            $string = $numbers;
        }

        // Initialisation
        srand(time());

        for ($i = 0; $i < $size; $i++) {
            $key = substr($string, (rand() % (strlen($string))), 1);

            if ($caseSensitive) {
                $key = (rand(0, 1) == 1) ? strtoupper($key) : strtolower($key);
            }
            $randKey .= $key;
        }
        return $randKey;
    }

    /**
     * Création d'un identifiant unique avec des chiffres, lettres et sensible à la case.
     *
     * @param int $size
     * @return string
     */
    public static function &makeIdentifier($size = 32) {
        $key = self::makeNewKey($size, true, true, true);
        return $key;
    }

    /**
     * Création d'un identifiant unique avec des chiffres.
     *
     * @param int $size
     * @return string
     */
    public static function &makeNumericIdentifier($size = 32) {
        $key = self::makeNewKey($size, false, true, false);
        return $key;
    }

    /**
     * Création d'un identifiant unique avec des lettres.
     *
     * @param int $size
     * @return string
     */
    public static function &createLetterIdentifier($size = 32) {
        $key = self::makeNewKey($size, true, false, false);
        return $key;
    }

    /**
     * Création d'un identifiant unique avec des lettres sensible à la case.
     *
     * @param int $size
     * @return string
     */
    public static function &createLetterCaseSensitiveIdentifier($size = 32) {
        $key = self::makeNewKey($size, true, false, true);
        return $key;
    }

    /**
     * Cryptage MD5 classique sans salt.
     * Cryptage de faible qualité.
     *
     * @param string $data
     * @return string
     */
    public static function cryptByMd5($data) {
        return md5($data);
    }

    /**
     * Cryptage MD5 via la fonction crypt() avec salt.
     * Cryptage qui nécessite l'activation d'une clé serveur.
     *
     * @param string $data
     * @param string $salt
     * @return string
     */
    public static function &cryptByMd5Alternative($data, $salt = "") {
        $cryptData = null;

        if (defined("CRYPT_MD5") && CRYPT_MD5) {
            if (empty($salt)) {
                $salt = self::makeIdentifier(8);
            }

            $salt = substr($salt, 0, 8);
            $cryptData = crypt($data, "$1$" . $salt . "$");
        } else {
            if (CoreLoader::isCallable("CoreLogger")) {
                CoreLogger::addException("Unsupported crypt method: CRYPT_MD5");
            }
            $cryptData = self::cryptByMd5($data);
        }
        return $cryptData;
    }

    /**
     * Cryptage MD5 amélioré spécialement pour le moteur.
     * Cryptage équilibré (suffisant pour la plus part du temps).
     *
     * @param string $data
     * @param string $salt
     * @return string
     */
    public static function cryptByMd5TrEngine($data, $salt = "") {
        if (empty($salt)) {
            $salt = self::makeIdentifier(8);
        }

        $salt = substr($salt, 0, 8);
        return "TR" . md5($data . $salt);
    }

    /**
     * Cryptage standard DES.
     * Cryptage moyen qui nécessite l'activation d'une clé serveur.
     *
     * @param string $data
     * @param string $salt
     * @return string
     */
    public static function &cryptByDes($data, $salt = "") {
        $cryptData = null;

        if (defined("CRYPT_STD_DES") && CRYPT_STD_DES) {
            if (empty($salt)) {
                $salt = self::makeIdentifier(2);
            }

            $salt = substr($salt, 0, 2);
            $cryptData = crypt($data, $salt);
        } else {
            if (CoreLoader::isCallable("CoreLogger")) {
                CoreLogger::addException("Unsupported crypt method: CRYPT_STD_DES");
            }
            $cryptData = self::cryptData($data);
        }
        return $cryptData;
    }

    /**
     * Cryptage SHA1.
     * Cryptage de moyen et sans salt.
     *
     * @param string $data
     * @return string
     */
    public static function cryptBySha1($data) {
        return sha1($data);
    }

    /**
     * Cryptage SSHA.
     * Cryptage de bonne qualité mais lent.
     *
     * @param string $data
     * @param string $salt
     * @return string
     */
    public static function cryptBySsha($data, $salt = "") {
        if (empty($salt)) {
            $salt = self::makeIdentifier(4);
        }

        $salt = substr($salt, 0, 4);
        return "{SSHA}" . base64_encode(pack("H*", sha1($data . $salt)) . $salt);
    }

    /**
     * Cryptage my411.
     * Cryptage de bonne qualité mais lent et sans salt.
     *
     * @param string $data
     * @param string $salt
     * @return string
     */
    public static function cryptByMy411($data) {
        return "*" . sha1(pack("H*", sha1($data)));
    }

    /**
     * Encodeur de chaine
     * Thanks Alexander Valyalkin @ 30-Jun-2004 08:41
     * http://fr2.php.net/manual/fr/function.md5.php
     *
     * @param $plain_text
     * @param $password
     * @param $iv_len
     * @return string
     */
    public static function &md5Encrypt($plain_text, $password, $iv_len = 16) {
        $plain_text .= "\x13";
        $n = strlen($plain_text);
        if ($n % 16)
            $plain_text .= str_repeat("\0", 16 - ($n % 16));

        $i = 0;
        $enc_text = self::getRandIv($iv_len);
        $iv = substr($password ^ $enc_text, 0, 512);
        while ($i < $n) {
            $block = substr($plain_text, $i, 16) ^ pack('H*', md5($iv));
            $enc_text .= $block;
            $iv = substr($block . $iv, 0, 512) ^ $password;
            $i += 16;
        }
        $enc_text = base64_encode($enc_text);
        return $enc_text;
    }

    /**
     * Décodeur de chaine
     * Thanks Alexander Valyalkin @ 30-Jun-2004 08:41
     * http://fr2.php.net/manual/fr/function.md5.php
     *
     * @param $enc_text
     * @param $password
     * @param $iv_len
     * @return string
     */
    public static function &md5Decrypt($enc_text, $password, $iv_len = 16) {
        $enc_text = base64_decode($enc_text);
        $n = strlen($enc_text);

        $i = $iv_len;
        $plain_text = '';
        $iv = substr($password ^ substr($enc_text, 0, $iv_len), 0, 512);
        while ($i < $n) {
            $block = substr($enc_text, $i, 16);
            $plain_text .= $block ^ pack('H*', md5($iv));
            $iv = substr($block . $iv, 0, 512) ^ $password;
            $i += 16;
        }
        $plain_text = preg_replace('/\\x13\\x00*$/', '', $plain_text);
        return $plain_text;
    }

    /**
     * Genere une valeur
     * Thanks Alexander Valyalkin @ 30-Jun-2004 08:41
     * http://fr2.php.net/manual/fr/function.md5.php
     *
     * @param $iv_len
     * @return string
     */
    private static function &getRandIv($iv_len) {
        $iv = "";
        while ($iv_len-- > 0) {
            $iv .= chr(mt_rand() & 0xff);
        }
        return $iv;
    }

}

?>