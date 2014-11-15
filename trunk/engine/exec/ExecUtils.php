<?php

namespace TREngine\Engine\Exec;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Fonction optimisé et utilitaire
 *
 * @author Sébastien Villemain
 */
class ExecUtils {

    /**
     * Indique si une valeur appartient à un tableau.
     * in_array() optimized function.
     *
     * @author robin at robinnixon dot com
     * http://www.php.net/manual/fr/function.in-array.php#92460
     *
     * @param string $needle
     * @param array $haystack
     * @return boolean
     */
    public static function inArray($needle, array $haystack) {
        $rslt = false;
        $top = sizeof($haystack) - 1;
        $bot = 0;

        while ($top >= $bot) {
            $p = floor(($top + $bot) / 2);

            if ($haystack[$p] < $needle) {
                $bot = $p + 1;
            } else if ($haystack[$p] > $needle) {
                $top = $p - 1;
            } else {
                $rslt = true;
                break;
            }
        }
        return $rslt;
    }

    /**
     * Indique si une valeur appartient à un tableau (tableau multiple).
     * Tableau à dimension multiple.
     * in_array()  multi array function.
     *
     * @param string $needle
     * @param array $haystack
     * @return boolean
     */
    public static function inMultiArray($needle, array $haystack) {
        $rslt = false;

        foreach ($haystack as $value) {
            if (is_array($value)) {
                if (self::inMultiArray($needle, $value)) {
                    $rslt = true;
                    break;
                }
            } else {
                if ($value === $needle) {
                    $rslt = true;
                    break;
                }
            }
        }
        return $rslt;
    }

}
