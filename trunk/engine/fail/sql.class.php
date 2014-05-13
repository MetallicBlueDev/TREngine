<?php

/**
 * Exception lancée par SQL.
 *
 * @author Sébastien Villemain
 */
class Fail_Sql extends Fail_Base {

    public function __construct($message) {
        parent::__construct($message, Fail_Base::FROM_SQL);
    }

}
