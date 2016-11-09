<?php

namespace TREngine\Engine\Fail;

require dirname(__FILE__) . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'SecurityCheck.php';

/**
 * Exception lancée par le module SQL.
 *
 * @author Sébastien Villemain
 */
class FailSql extends FailBase {

    /**
     * Exception déclenchée par SQL.
     *
     * @param string $message
     */
    public function __construct(string $message) {
        parent::__construct($message, FailBase::FROM_SQL);
    }

}
