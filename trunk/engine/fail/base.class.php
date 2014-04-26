<?php

/**
 * Base d'une exception du moteur.
 *
 * @author Sebastien Villemain
 */
abstract class Fail_Base extends Exception {

    /**
     * Une erreur généré par une couche basse du moteur.
     */
    const FROM_ENGINE = -1;

    /**
     * Une erreur généré la couche SQL du moteur.
     */
    const FROM_SQL = 10;

    /**
     * Une erreur généré le chargeur de classe.
     */
    const FROM_LOADER = 20;

    public function __construct($message, $failSourceNumber = self::FROM_ENGINE) {
        parent::__construct($message, $failSourceNumber, null);
    }

    /**
     * Retourne le nom de la source de l'exception.
     *
     * @return string
     */
    public function getFailSourceName() {
        $sourceName = get_called_class();
        $pos = strpos($sourceName, "_");

        if ($pos > 1) {
            $sourceName = substr($sourceName, $pos + 1, strlen($sourceName) - $pos - 1);
        }
        return $sourceName;
    }

    /**
     * Retour une description de base sur l'exception.
     *
     * @return string
     */
    public function getFailInformation() {
        return "Exception " . $this->getFailSourceName() . " (" . $this->getCode() . ") : " . $this->getMessage();
    }

}
