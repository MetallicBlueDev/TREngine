<?php

namespace TREngine\Engine\Fail;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Exception lancée par le cache.
 *
 * @author Sébastien Villemain
 */
class FailCache extends FailBase {

    /**
     * Exception déclenchée par le cache.
     * 
     * @param string $message
     */
    public function __construct(string $message) {
        parent::__construct($message, FailBase::FROM_CACHE);
    }

}
