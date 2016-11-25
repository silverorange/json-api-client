<?php

namespace silverorange;

class ToOneRelationship
{
    // {{{ protected properties

    protected $resource;

    // }}}
    // {{{ public function __construct()

    public function __construct(ResourceIdentifier $resource)
    {
        $this->resource = $resource;
    }

    // }}}
    // {{{ public function getType()

    public function getType()
    {
        return $this->resource->getType();
    }

    // }}}
    // {{{ public function getId()

    public function getId()
    {
        return $this->resource->getId();
    }

    // }}}
    // {{{ public function get()

    public function get()
    {
        if ($this->resource instanceof ResourceIdentifier) {
            $this->resource = $this->resource->getResource();
        }

        return $this->resource;
    }

    // }}}
    // {{{ public function set()

    public function set(ResourceIdentifier $resource)
    {
        $this->resource = $resource;
    }

    // }}}
    // {{{ public function encodeIdentifier()

    public function encodeIdentifier()
    {
        return $this->resource->encodeIdentifier();
    }

    // }}}
}
