<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

namespace silverorange\JsonApiClient;

use silverorange\JsonApiClient\Exception\InvalidResourceTypeException;

class ToOneRelationship implements ResourceStoreAccess
{
    // {{{ protected properties

    protected $resource = null;
    protected $type = null;
    protected $is_modified = false;

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

    public function set(AbstractResource $resource = null)
    {
        if ($resource instanceof AbstractResource &&
            $this->type !== $resource->getType()
        ) {
            throw new InvalidResourceTypeException(
                sprintf(
                    'Provided resource type “%s” does not match “%s”.',
                    $resource->getType(),
                    $this->type
                )
            );
        }

        $old_resource_id = ($this->resource instanceof AbstractResource)
            ? $this->resource->getId()
            : null;

        $new_resource_id = ($resource instanceof AbstractResource)
            ? $resource->getId()
            : null;

        if ($old_resource_id !== $new_resource_id) {
            $this->is_modified = true;
            $this->resource = $resource;
        }
    }

    // }}}
    // {{{ public function encodeIdentifier()

    public function encodeIdentifier(array $options = [])
    {
        if ($this->resource instanceof AbstractResource) {
            return $this->resource->encodeIdentifier();
        }

        return [ 'data' => null ];
    }

    // }}}
    // {{{ public function save()

    public function save()
    {
        if ($this->resource instanceof Resource) {
            $this->resource->save();
        }
    }

    // }}}
    // {{{ public function isModified()

    public function isModified()
    {
        $is_modified = false;

        if ($this->resource instanceof Resource) {
            $is_modified = $this->resource->isModified();
        }

        return $is_modified;
    }

    // }}}
    // {{{ public function isSelfModified()

    public function isSelfModified()
    {
        return $this->is_modified;
    }

    // }}}
}
