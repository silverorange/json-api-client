<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

namespace silverorange\JsonApiClient;

class ResourceErrorException extends \Exception
{
    // {{{ protected properties

    /**
     * @var array
     */
    protected $error = [];

    // }}}
    // {{{ public function __construct()

    public function __construct($message, $code = 0, array $error)
    {
        if ($message === '' && isset($error['detail'])) {
            $message = $error['detail'];
        }
        parent::__construct($message, $code);
    }

    // }}}
    // {{{ public function getError()

    public function getError()
    {
        return $this->error;
    }

    // }}}
}
