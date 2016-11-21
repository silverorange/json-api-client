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
    // {{{ public function decode()

    public function decode($json)
    {
        $data = json_decode($json, true);

        $this->id = $data['data']['id'];
        $this->type = $data['data']['type'];
        $this->attributes = $data['data']['attributes'];

        if (array_key_exists('relationships', $data['data'])) {
            foreach ($data['data']['relationships'] as $key => $resource) {
                // TODO: Deal with collections and nulls
                if ($resource['data'] === null) {
                    $this->relationships[$key] = null;
                } elseif (array_key_exists('id', $resource['data'])) {
                    $this->relationships[$key] = new ToOneRelationship(
                        new ResourceIdentifier(
                            $this->store,
                            $resource['data']['type'],
                            $resource['data']['id']
                        )
                    );
                } else {
                    $collection = null;
                    foreach ($resource['data'] as $data) {
                        if (!$collection instanceof ResourceIdentifierCollection) {
                            $collection = new ResourceIdentifierCollection($data['type']);
                        }

                        $collection->append(
                            new ResourceIdentifier(
                                $this->store,
                                $data['type'],
                                $data['id']
                            )
                        );
                    }

                    $this->relationships[$key] = new ToManyRelationship(
                        $collection
                    );
                }
            }
        }
    }

    // }}}
    // {{{ public function encode()

    public function encode()
    {
        $relationships = [];

        // TODO: Deal with to many relationships
        foreach ($this->relationships as $name => $relationship) {
            if ($relationship instanceof ToOneRelationship) {
                $relationships[$name] = [
                    'data' => [
                        'id' => $relationship->getId(),
                        'type' => $relationship->getType()
                    ]
                ];
            }
        }

        return json_encode(
            [
                'data' => [
                    'id' => $this->getId(),
                    'type' => $this->getType(),
                    'attributes' => $this->attributes,
                    'relationships' => $relationships,
                ]
            ],
            JSON_PRETTY_PRINT
        );
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
}
