<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Exception inconnue lancée par le moteur.
 *
 * @author Sébastien Villemain
 */
class FailEngine extends FailBase {

    public function __construct($message) {
        parent::__construct($message, FailBase::FROM_ENGINE);
    }

}
