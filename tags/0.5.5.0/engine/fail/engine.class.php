<?php

/**
 * Exception inconnue lancée par le moteur.
 *
 * @author Sebastien Villemain
 */
class Fail_Engine extends Fail_Base {

    public function __construct($message) {
        parent::__construct($message, Fail_Base::FROM_ENGINE);
    }

}
