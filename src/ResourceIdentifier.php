<?php

namespace silverorange\JsonApiClient;

class ResourceIdentifier implements \Serializable
{
    use ResourceStoreAccessTrait;
    // {{{ protected properties

    protected $type = null;
    protected $id = null;

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
