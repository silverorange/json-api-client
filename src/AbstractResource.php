<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

namespace silverorange\JsonApiClient;

use silverorange\JsonApiClient\Exception\InvalidDataException;
use silverorange\JsonApiClient\Exception\NoResourceStoreException;

abstract class AbstractResource implements ResourceStoreAccess, \Serializable
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
    // {{{ public function encodeIdentifier()

    public function encodeIdentifier(array $options = [])
    {
        return [
            'data' => [
                'id' => $this->getId(),
                'type' => $this->getType(),
            ]
        ];
    }

    // }}}
    // {{{ abstract public function encode()

    abstract public function encode(array $options = []);

    // }}}
    // {{{ abstract public function decode()

    abstract public function decode(array $data);

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
    // {{{ protected function validateData()

    protected function validateData(array $data)
    {
        if (!isset($data['type'])) {
            throw new InvalidDataException(
                'Resource data is missing required "type" field.',
                0,
                $data
            );
        }

        if (!is_string($data['type'])) {
            throw new InvalidDataException(
                'Resource data "type" field is not a string.',
                0,
                $data
            );
        }

        if (!isset($data['id'])) {
            throw new InvalidDataException(
                'Resource data is missing required "id" field.',
                0,
                $data
            );
        }

        if (!is_string($data['id'])) {
            throw new InvalidDataException(
                'Resource data "id" field is not a string.',
                0,
                $data
            );
        }
    }

    // }}}
}
