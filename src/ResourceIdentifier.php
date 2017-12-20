<?php

namespace silverorange\JsonApiClient;

class ResourceIdentifier extends AbstractResource
{
    // {{{ protected properties

    protected $type = null;

    // }}}
    // {{{ public function getType()

    public function getType()
    {
        return $this->type;
    }

    // }}}
    // {{{ public function encode()

    public function encode(array $options = [])
    {
        return $this->encodeIdentifier($options);
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
