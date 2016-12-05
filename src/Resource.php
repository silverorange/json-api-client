<?php

namespace silverorange;

class Resource extends ResourceIdentifier
{
    // {{{ protected properties

    protected $id;
    protected $type;
    protected $store;
    protected $attributes = array();
    protected $relationships = array();

    // }}}
    // {{{ public function __construct()

    public function __construct(ResourceStore $store)
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
    // {{{ public function get()

    public function get($key)
    {
        if (array_key_exists($key, $this->relationships)) {
            return $this->relationships[$key]->get();
        }

        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        throw new \Exception(
            sprintf(
                'Unable to get property “%s” on resource type “%s”',
                $key,
                $this->type
            )
        );
    }

    // }}}
    // {{{ public function set()

    public function set($key, $value)
    {
        if (array_key_exists($key, $this->relationships)) {
            $this->relationships[$key]->set($value);
        } elseif (array_key_exists($key, $this->attributes)) {
            $this->attributes[$key] = $value;
        } else {
            throw new \Exception(
                sprintf(
                    'Unable to set property “%s” on resource type “%s”',
                    $key,
                    $this->type
                )
            );
        }
    }

    // }}}
    // {{{ public function encode()

    public function encode()
    {
        $relationships = [];

        foreach ($this->relationships as $name => $relationship) {
            if ($relationship instanceof ToOneRelationship ||
                $relationship instanceof ToManyRelationship) {
                $relationships[$name] = $relationship->encodeIdentifier();
            } else {
                $relationships[$name] = [
                    'data' => null
                ];
            }
        }

        $data = [
            'data' => []
        ];

        if ($this->getId() != '') {
            $data['data']['id'] = $this->getId();
        }

        $data['data']['type'] = $this->getType();
        $data['data']['attributes'] = $this->attributes;
        $data['data']['relationships'] = $relationships;

        return $data;
    }

    // }}}
    // {{{ public function encodeIdentifier()

    public function encodeIdentifier()
    {
        return parent::encodeIdentifier();
    }

    // }}}
    // {{{ public function decode()

    public function decode($data)
    {
        $this->id = $data['id'];
        $this->type = $data['type'];
        $this->attributes = $data['attributes'];

        if (array_key_exists('relationships', $data)) {
            foreach ($data['relationships'] as $key => $resources) {
                if ($resources['data'] === null) {
                    $this->relationships[$key] = null;
                } elseif (array_key_exists('id', $resources['data'])) {
                    $resource = $resources['data'];

                    $this->relationships[$key] = new ToOneRelationship(
                        new ResourceIdentifier(
                            $this->store,
                            $resource['type'],
                            $resource['id']
                        )
                    );
                } else {
                    $collection = null;
                    foreach ($resources['data'] as $resource) {
                        if (!$collection instanceof ResourceCollection) {
                            $collection = new ResourceCollection(
                                $this->store,
                                $resource['type']
                            );
                        }

                        $collection->add(
                            new ResourceIdentifier(
                                $this->store,
                                $resource['type'],
                                $resource['id']
                            )
                        );
                    }

                    if ($collection instanceof ResourceCollection) {
                        $this->relationships[$key] = new ToManyRelationship(
                            $collection
                        );
                    }
                }
            }
        }
    }

    // }}}
}
