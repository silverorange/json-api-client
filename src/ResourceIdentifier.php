<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

namespace silverorange\JsonApiClient;

class ResourceIdentifier implements ResourceStoreAccess, \Serializable
{
    // {{{ private properties

    private $type = null;

    // }}}
    // {{{ protected properties

    protected $id = null;
    protected $store = null;

    // }}}
    // {{{ public function setStore()

    public function setStore(ResourceStore $store)
    {
        $this->store = $store;
    }

    // }}}
    // {{{ public function getType()

    public function getType()
    {
        return $this->type;
    }

    // }}}
    // {{{ public function getId()

    public function getId()
    {
        return $this->id;
    }

    // }}}
    // {{{ public function getResource()

    public function getResource()
    {
        $this->checkStore();

        return $this->store->find($this->getType(), $this->getId());
    }

    // }}}
    // {{{ public function encode()

    public function encode()
    {
        return $this->encodeIndentifier();
    }

    // }}}
    // {{{ public function encodeIdentifier()

    public function encodeIdentifier()
    {
        return [
            'data' => [
                'id' => $this->getId(),
                'type' => $this->getType(),
            ]
        ];
    }

    // }}}
    // {{{ public function decode()

    public function decode(array $data)
    {
        $this->type = $data['type'];
        $this->id = $data['id'];
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

    // Serializable interface
    // {{{ public function serialize()

    public function serialize()
    {
        return serialize([
            'type' => $this->type,
            'id' => $this->id
        ]);
    }

    // }}}
    // {{{ public function unserialize()

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        $this->type = $data['type'];
        $this->id = $data['id'];
    }

    // }}}
}
