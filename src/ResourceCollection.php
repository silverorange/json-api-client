<?php

namespace silverorange;

class ResourceCollection implements \Countable, \IteratorAggregate
{
    // {{{ protected properties

    protected $type;
    protected $collection = [];

    // }}}
    // {{{ public function __construct()

    public function __construct($type)
    {
        $this->type = $type;
    }

    // }}}
    // {{{ public function count()

    public function count()
    {
        return count($this->collection);
    }

    // }}}
    // {{{ public function add()

    public function add(ResourceIdentifier $resource)
    {
        if ($resource->getType() !== $this->type) {
            throw new \Exception(
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
            throw new \Exception(
                sprintf('Unable to get ResourceIdentifier of ID %s.', $id)
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
    // {{{ public function getIterator()

    public function getIterator()
    {
        return new ResourceCollectionIterator($this);
    }

    // }}}
}
