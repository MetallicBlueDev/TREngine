<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Exception lancée par le chargeur de classe.
 *
 * @author Sébastien Villemain
 */
class Fail_Loader extends Fail_Base {

    public function __construct($message) {
        parent::__construct($message, Fail_Base::FROM_LOADER);
    }

}
