<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Exception inconnue lancée par le moteur.
 *
 * @author Sébastien Villemain
 */
class Fail_Engine extends Fail_Base {

    public function __construct($message) {
        parent::__construct($message, Fail_Base::FROM_ENGINE);
    }

}
