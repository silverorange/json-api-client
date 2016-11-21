<?php

namespace silverorange;

class ToManyRelationship
{
    // {{{ protected properties

    protected $pointer_collection;

    // }}}
    // {{{ public function __construct()

    public function __construct(ResourcePointerCollection $pointer_collection)
    {
        $this->pointer_collection = $pointer_collection;
    }

    // }}}
    // {{{ public function get()

    public function get()
    {
        return $this->pointer_collection;
    }

    // }}}
    // {{{ public function set()

    public function set(ResourcePointerCollection $pointer_collection)
    {
        $this->pointer_collection = $pointer_collection;
    }

    // }}}
}
