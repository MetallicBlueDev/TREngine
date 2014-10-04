<?php
if (!defined("TR_ENGINE_INDEX")) {
    require(".." . DIRECTORY_SEPARATOR . "core" . DIRECTORY_SEPARATOR . "secure.class.php");
    Core_Secure::checkInstance();
}

/**
 * Mesure de durée.
 *
 * @author Sébastien Villemain
 */
class Exec_TimeMarker {

    /**
     * Marqueur de début.
     *
     * @var array
     */
    private static $startTime = array();

    /**
     * Marqueur de fin.
     *
     * @var array
     */
    private static $finishTime = array();

    /**
     * Marque le temps de début de génération.
     *
     * @param string $name Nom de la mesure.
     */
    public static function startMeasurement($name) {
        self::$startTime[$name] = self::getMaker();
    }

    /**
     * Marque le temps de fin de génération.
     *
     * @param string $name Nom de la mesure.
     */
    public static function stopMeasurement($name) {
        self::$finishTime[$name] = self::getMaker();
    }

    /**
     * Retourne le temps courant en micro seconde.
     *
     * @return float
     */
    private static function getMaker() {
        return microtime(true);
    }

    /**
     * Retourne la durée mesurée en milliseconde.
     *
     * @return int le temps de génération en milliseconde.
     */
    public static function &getMeasurement($name) {
        $rslt = 0;

        if (isset(self::$startTime[$name]) && isset(self::$finishTime[$name])) {
            $rslt = self::$finishTime[$name] - self::$startTime[$name];
            $rslt = round($rslt, 4) * 1000;
        }
        return $rslt;
    }

}
