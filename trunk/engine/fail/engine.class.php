<?php

/**
 * Description of engine
 *
 * @author Sebastien Villemain
 */
class Fail_Engine extends Exception {

    /**
     * Une erreur généré par une couche basse du moteur.
     */
    const FROM_ENGINE = -1;

    /**
     * Une erreur généré par PHP.
     */
    const FROM_PHP = 0;

    /**
     * Une erreur généré la couche SQL du moteur.
     */
    const FROM_SQL = 10;

    protected function __construct($message, $failSourceNumber = self::FROM_ENGINE) {
        parent::__construct($message, $failSourceNumber);
    }

    public function getFailSourceName() {
        $sourceName = "";

        foreach ($this as $key => $value) {
            if ($value === $this->getCode()) {
                $sourceName = $key;
                break;
            }
        }
        return $sourceName;
    }

    public function getFailInformation() {
        return "Exception " . $this->getFailSourceName() . "(" . $this->getCode() . ") : " . $this->getMessage();
    }

}
