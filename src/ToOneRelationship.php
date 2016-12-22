<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

namespace silverorange\JsonApiClient;

use silverorange\JsonApiClient\Exception\InvalidResourceTypeException;

class ToOneRelationship implements ResourceStoreAccess
{
    // {{{ protected properties

    protected $resource = null;
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
        if ($this->resource instanceof ResourceStoreAccess) {
            $this->resource->setStore($store);
        }
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
        if ($this->type !== $resource->getType()) {
            throw new InvalidResourceTypeException(
                sprintf(
                    'Provided resource type “%s” does not match “%s”.',
                    $resource->getType(),
                    $this->type
                )
            );
        }

        $this->resource = $resource;
    }

    // }}}
    // {{{ public function encodeIdentifier()

    public function encodeIdentifier()
    {
        if ($this->resource instanceof ResourceIdentifier) {
            return $this->resource->encodeIdentifier();
        }

        return [ 'data' => null ];
    }

    // }}}
    // {{{ public function save()

    public function save()
    {
        if ($this->resource instanceof Resource && $this->resource->isNew()) {
            $this->resource->save();
        }
    }

    // }}}
}
