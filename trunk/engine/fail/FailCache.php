<?php
require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Exception lancée par le cache.
 *
 * @author Sébastien Villemain
 */
class FailCache extends FailBase {

    public function __construct($message) {
        parent::__construct($message, FailBase::FROM_CACHE);
    }

}
