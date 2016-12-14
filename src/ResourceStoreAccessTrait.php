<?php

namespace silverorange\JsonApiClient;

trait ResourceStoreAccessTrait
{
    // {{{ protected properties

    protected $store = null;

    // }}}
    // {{{ public function setStore()

    public function setStore(ResourceStore $store)
    {
        $this->store = $store;
    }

    // }}}
    // {{{ protected function checkStore()

    protected function checkStore()
    {
        if (!$this->store instanceof ResourceStore) {
            throw new NoResourceStoreException(
                'No resource store available to this object. '.
                'Call the setStore() method.'
            );
        }
    }

    // }}}
}
