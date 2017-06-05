<?php

namespace silverorange\JsonApiClient\Exception;

class InvalidDataException extends \Exception
{
    // {{{ protected properties

    /**
     * @var array
     */
    protected $data;

    // }}}
    // {{{ public function __construct()

    public function __construct($message, $error = 0, array $data = null)
    {
        parent::__construct($message, $error);
        $this->data = $data;
    }

    // }}}
    // {{{ public function getData()

    public function getData()
    {
        return $this->data;
    }

    // }}}
}
