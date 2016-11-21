<?php

namespace silverorange;

class ToManyRelationship
{
    // {{{ protected properties

    protected $resource_collection;

    // }}}
    // {{{ public function __construct()

    public function __construct(
        ResourceIdentifierCollection $resource_collection
    ) {
    
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

    public function set(ResourceIdentifierCollection $resource_collection)
    {
        $this->resource_collection = $resource_collection;
    }

    // }}}
}
