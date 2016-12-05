<?php

namespace silverorange;

class ResourceIdentifier
{
    // {{{ protected properties

    protected $store = null;
    protected $type = null;
    protected $id = null;

    // }}}
    // {{{ public function __construct()

    public function __construct(ResourceStore $store, $type, $id)
    {
        $this->store = $store;
        $this->type = $type;
        $this->id = $id;
    }

    // }}}
    // {{{ public function getType()

    public function getType()
    {
        return $this->type;
    }

    // }}}
    // {{{ public function getId()

    public function getId()
    {
        return $this->id;
    }

    // }}}
    // {{{ public function getResource()

    public function getResource()
    {
        return $this->store->find($this->getType(), $this->getId());
    }

    // }}}
    // {{{ public function encode()

    public function encode()
    {
        return $this->encodeIndentifier();
    }

    // }}}
    // {{{ public function encodeIdentifier()

    public function encodeIdentifier()
    {
        return [
            'data' => [
                'id' => $this->getId(),
                'type' => $this->getType()
            ]
        ];
    }

    // }}}
}
