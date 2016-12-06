<?php

namespace silverorange;

use Countable;
use Serializable;
use IteratorAggregate;

class ResourceCollection implements Countable, Serializable, IteratorAggregate
{
    use ResourceStoreAccessTrait;
    // {{{ protected properties

    protected $store = null;
    protected $type = null;
    protected $collection = [];

    // }}}
    // {{{ public function __construct()

    public function __construct($type)
    {
        $this->type = $type;
    }

    // }}}
    // {{{ public function add()

    public function add(ResourceIdentifier $resource)
    {
        if ($resource->getType() !== $this->type) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unable to add resource of type “%s” to '.
                    'collection of type “%s”',
                    $valuye->getType(),
                    $this->type
                )
            );
        }

        $this->collection[$resource->getId()] = $resource;
    }

    // }}}
    // {{{ pulbic function get()

    public function get($id)
    {
        if (!array_key_exists($id, $this->collection)) {
            throw new \OutOfBoundsException(
                sprintf('Unable to get “%s” with id “%s”.', $this->type, $id)
            );
        }

        $resource = $this->collection[$id];
        if ($resource instanceof ResourceIdentifier) {
            $this->add($resource->getResource());
        }

        return $this->collection[$id];
    }

    // }}}
    // {{{ public function getKeys()

    public function getKeys()
    {
        return array_keys($this->collection);
    }

    // }}}
    // {{{ public function encode()

    public function encode()
    {
        $data = [];

        // Don't use the object itself to get the interator.
        // Prevents lazy loading.
        foreach ($this->collection as $resource) {
            $data[] = $resource->encode();
        }

        return $data;
    }

    // }}}
    // {{{ public function encodeIdentifier()

    public function encodeIdentifier()
    {
        $data = [];

        // Don't use the object itself to get the interator.
        // Prevents lazy loading.
        foreach ($this->collection as $resource) {
            $data[] = $resource->encodeIdentifier();
        }

        return $data;
    }

    // }}}
    // {{{ public function decode()

    public function decode(array $collection)
    {
        $this->checkStore();

        foreach ($collection as $data) {
            $resource = new Resource();
            $resource->setStore($this->store);
            $resource->decode($data);

            $this->add($resource);
        }
    }

    // }}}

    // Countable interface
    // {{{ public function count()

    public function count()
    {
        return count($this->collection);
    }

    // }}}

    // Serializable interface
    // {{{ public function serialize()

    public function serialize()
    {
        return serialize([
            'type' => $this->type,
            'collection' => $this->collection
        ]);
    }

    // }}}
    // {{{ public function unserialize()

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        $this->type = $data['type'];
        $this->collection = $data['collection'];
    }

    // }}}

    // IteratorAggregate interface
    // {{{ public function getIterator()

    public function getIterator()
    {
        return new ResourceCollectionIterator($this);
    }

    // }}}
}
