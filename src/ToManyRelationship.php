<?php

namespace silverorange\JsonApiClient;

class ToManyRelationship
{
    // {{{ protected properties

    protected $resource_collection;

    // }}}
    // {{{ public function __construct()

    public function __construct(ResourceCollection $resource_collection)
    {
        $this->resource_collection = $resource_collection;
    }

    // }}}
    // {{{ public function get()

    public function get()
    {
        return $this->resource_collection;
    }

    // }}}
    // {{{ public function set()

    public function set(ResourceCollection $resource_collection)
    {
        $this->resource_collection = $resource_collection;
    }

    // }}}
    // {{{ public function encodeIdentifier()

    public function encodeIdentifier()
    {
        return $this->resource_collection->encodeIdentifier();
    }

    // }}}
}
