<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

namespace silverorange\JsonApiClient;

use silverorange\JsonApiClient\Exception\InvalidDataException;
use silverorange\JsonApiClient\Exception\NoResourceStoreException;

class ResourceIdentifier extends AbstractResource
{
    // {{{ public function encode()

    public function encode(array $options = [])
    {
        return $this->encodeIndentifier($options);
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
    // {{{ public function decode()

    public function decode(array $data)
    {
        $this->validateData($data);

        $this->type = $data['type'];
        $this->id = $data['id'];
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
