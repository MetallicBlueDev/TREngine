<?php

namespace PassionEngine\Engine\Fail;

/**
 * Exception lancée par le cache.
 *
 * @author Sébastien Villemain
 */
class FailCache extends FailBase
{

    /**
     * Nouvelle erreur de cache.
     *
     * @param string $message
     * @param string $failCode
     * @param array $failArgs
     * @param bool $useArgsInTranslate
     */
    public function __construct(string $message,
                                string $failCode = "",
                                array $failArgs = array(),
                                bool $useArgsInTranslate = false)
    {
        parent::__construct($message,
                            $failCode,
                            $failArgs,
                            $useArgsInTranslate);
    }
}