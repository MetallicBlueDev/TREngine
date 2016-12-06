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
     * Création d'un identifiant unique avec des chiffres, lettres et sensible à la case.
     *
     * @param int $size
     * @return string
     */
    public static function &makeIdentifier(int $size = 32) {
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
    public static function &makeLetterIdentifier($size = 32) {
        $key = self::makeNewKey($size, true, false, false);
        return $key;
    }

    /**
     * Création d'un identifiant unique avec des lettres sensible à la case.
     *
     * @param int $size
     * @return string
     */
    public static function &makeLetterCaseSensitiveIdentifier($size = 32) {
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
    public static function &cryptByMd5($data) {
        $cryptData = md5($data);
        return $cryptData;
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
        $cryptData = "";

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
    public static function &cryptByMd5TrEngine($data, $salt = "") {
        if (empty($salt)) {
            $salt = self::makeIdentifier(8);
        }

        $salt = substr($salt, 0, 8);
        $cryptData = "TR" . md5($data . $salt);
        return $cryptData;
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
        $cryptData = "";

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
    public static function &cryptBySha1($data) {
        $cryptData = sha1($data);
        return $cryptData;
    }

    /**
     * Cryptage SSHA.
     * Cryptage de bonne qualité mais lent.
     *
     * @param string $data
     * @param string $salt
     * @return string
     */
    public static function &cryptBySsha($data, $salt = "") {
        if (empty($salt)) {
            $salt = self::makeIdentifier(4);
        }

        $salt = substr($salt, 0, 4);
        $cryptData = "{SSHA}" . base64_encode(pack("H*", sha1($data . $salt)) . $salt);
        return $cryptData;
    }

    /**
     * Cryptage my411.
     * Cryptage de bonne qualité mais lent et sans salt.
     *
     * @param string $data
     * @param string $salt
     * @return string
     */
    public static function &cryptByMy411($data) {
        $cryptData = "*" . sha1(pack("H*", sha1($data)));
        return $cryptData;
    }

    /**
     * Encodeur de chaine.
     * Thanks Alexander Valyalkin @ 30-Jun-2004 08:41
     * http://fr2.php.net/manual/fr/function.md5.php
     *
     * @param string $plainText
     * @param string $password
     * @param int $ivLen
     * @return string
     */
    public static function &md5Encrypt($plainText, $password, $ivLen = 16) {
        $plainText .= "\x13";
        $n = strlen($plainText);

        if ($n % 16) {
            $plainText .= str_repeat("\0", 16 - ($n % 16));
        }

        $i = 0;
        $encText = self::getRandIv($ivLen);
        $iv = substr($password ^ $encText, 0, 512);

        while ($i < $n) {
            $block = substr($plainText, $i, 16) ^ pack('H*', md5($iv));
            $encText .= $block;
            $iv = substr($block . $iv, 0, 512) ^ $password;
            $i += 16;
        }
        $encText = base64_encode($encText);
        return $encText;
    }

    /**
     * Décodeur de chaine.
     * Thanks Alexander Valyalkin @ 30-Jun-2004 08:41
     * http://fr2.php.net/manual/fr/function.md5.php
     *
     * @param string $encText
     * @param string $password
     * @param int $ivLen
     * @return string
     */
    public static function &md5Decrypt($encText, $password, $ivLen = 16) {
        $encText = base64_decode($encText);
        $n = strlen($encText);

        $i = $ivLen;
        $plainText = '';
        $iv = substr($password ^ substr($encText, 0, $ivLen), 0, 512);

        while ($i < $n) {
            $block = substr($encText, $i, 16);
            $plainText .= $block ^ pack('H*', md5($iv));
            $iv = substr($block . $iv, 0, 512) ^ $password;
            $i += 16;
        }
        $plainText = preg_replace('/\\x13\\x00*$/', '', $plainText);
        return $plainText;
    }

    /**
     * Création d'une clé générée aléatoirement.
     *
     * @param int $size
     * @param bool $letter
     * @param bool $number
     * @param bool $caseSensitive
     * @return string
     */
    private static function &makeNewKey(int $size = 32, bool $letter = true, bool $number = true, bool $caseSensitive = true): string {
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
     * Génère une valeur.
     * Thanks Alexander Valyalkin @ 30-Jun-2004 08:41
     * http://fr2.php.net/manual/fr/function.md5.php
     *
     * @param $ivLen
     * @return string
     */
    private static function &getRandIv($ivLen) {
        $iv = "";

        while ($ivLen-- > 0) {
            $iv .= chr(mt_rand() & 0xff);
        }
        return $iv;
    }

}
