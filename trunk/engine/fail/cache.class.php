<?php

/**
 * Exception lancée par le cache.
 *
 * @author Sebastien Villemain
 */
class Fail_Cache extends Fail_Base {

    public function __construct($message) {
        parent::__construct($message, Fail_Base::FROM_CACHE);
    }

}
