<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

namespace silverorange\JsonApiClient;

use silverorange\JsonApiClient\Exception\InvalidResourceTypeException;

class ToManyRelationship implements ResourceStoreAccess
{
    // {{{ protected properties

    protected $resource_collection = null;
    protected $type = null;

    // }}}
    // {{{ public function __construct()

    public function __construct($type)
    {
        $this->type = $type;
    }

    // }}}
    // {{{ public function setStore()

    public function setStore(ResourceStore $store)
    {
        if ($this->resource_collection instanceof ResourceStoreAccess) {
            $this->resource_collection->setStore($store);
        }
    }

    // }}}
    // {{{ public function getType()

    public function getType()
    {
        return $this->type;
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
        if ($this->type !== $resource_collection->getType()) {
            throw new InvalidResourceTypeException(
                sprintf(
                    'Provided resource type “%s” does not match “%s”.',
                    $resource->getType(),
                    $this->type
                )
            );
        }

        $this->resource_collection = $resource_collection;
    }

    // }}}
    // {{{ public function encodeIdentifier()

    public function encodeIdentifier(array $options = [])
    {
        if ($this->resource_collection instanceof ResourceCollection) {
            return $this->resource_collection->encodeIdentifier($options);
        }

        return [ 'data' => [] ];
    }

    // }}}
}
