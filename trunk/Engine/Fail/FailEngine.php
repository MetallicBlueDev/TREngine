<?php

namespace TREngine\Engine\Fail;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Exception inconnue lancée par le moteur.
 *
 * @author Sébastien Villemain
 */
class FailEngine extends FailBase {

    /**
     * Exception déclenchée par une erreur interne.
     *
     * @param string $message
     */
    public function __construct(string $message) {
        parent::__construct($message, FailBase::FROM_ENGINE);
    }

}
