<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

namespace silverorange\JsonApiClient\Exception;

class ResourceErrorException extends \Exception
{
    // {{{ protected properties

    /**
     * @var array
     */
    protected $error = [];

    // }}}
    // {{{ public function __construct()

    public function __construct($message, $code, array $error)
    {
        if ($message === '' && isset($error['detail'])) {
            if (is_string($error['detail'])) {
                // Standard JSON API error object.
                $message = $error['detail'];
            } elseif (is_array($error['detail'])i && count($error['detail']) > 0) {
                // Extended error object from jsonapi-server
                $detail = $error['detail'][0];
                $message = $detail['message'];
            }
        }

        parent::__construct($message, $code);

        $this->error = $error;
    }

    // }}}
    // {{{ public function getError()

    public function getError()
    {
        return $this->error;
    }

    // }}}
}
