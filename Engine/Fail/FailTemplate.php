<?php

namespace TREngine\Engine\Fail;

/**
 * Exception lancée par le moteur de template.
 *
 * @author Sébastien Villemain
 */
class FailTemplate extends FailBase
{

    /**
     * Nouvelle erreur de template.
     *
     * @param string $message
     * @param string $failCode
     * @param array $failArgs
     */
    public function __construct(string $message, string $failCode = "", array $failArgs = array())
    {
        parent::__construct($message,
                            $failCode,
                            $failArgs);
    }
}