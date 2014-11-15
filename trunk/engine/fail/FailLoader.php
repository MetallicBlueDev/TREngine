<?php

namespace TREngine\Engine\Fail;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Exception lancée par le chargeur de classe.
 *
 * @author Sébastien Villemain
 */
class FailLoader extends FailBase {

    public function __construct($message) {
        parent::__construct($message, FailBase::FROM_LOADER);
    }

}
