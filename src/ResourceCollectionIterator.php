<?php

namespace silverorange;

class ResourceCollectionIterator implements \Iterator
{
    // {{{ protected properties

    protected $collection;
    protected $key;
    protected $current = 0;

    // }}}
    // {{{ public function __construct()

    public function __construct(ResourceCollection $collection)
    {
        $this->collection = $collection;
        $this->keys = $collection->getKeys();
    }

    // }}}
    // {{{ public function current()

    public function current()
    {
        return $this->collection->get($this->keys[$this->current]);
    }

    // }}}
    // {{{ public function key()

    public function key()
    {
        return $this->keys[$this->current];
    }

    // }}}
    // {{{ public function next()

    public function next()
    {
        $this->current++;
    }

    // }}}
    // {{{ public function rewind()

    public function rewind()
    {
        $this->current = 0;
    }

    // }}}
    // {{{ public function valid()

    public function valid()
    {
        return isset($this->keys[$this->current]);
    }

    // }}}
}
