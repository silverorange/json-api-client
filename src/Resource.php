<?php

namespace silverorange\JsonApiClient;

abstract class Resource extends ResourceIdentifier implements ResourceStoreAccess
{
    // {{{ protected properties

    protected $attributes = [];
    protected $relationships = [];

    // }}}
    // {{{ public function __construct()

    public function __construct()
    {
        $this->initAttributes();
        $this->initRelationships();
    }

    // }}}
    // {{{ public function setStore()

    public function setStore(ResourceStore $store)
    {
        parent::setStore($store);

        foreach ($this->relationships as $key => $relationship) {
            if ($relationship instanceof ResourceStoreAccess) {
                $relationship->setStore($store);
            }
        }
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

        throw new InvalidPropertyException(
            sprintf(
                'Unable to get property “%s” on resource type “%s”',
                $key,
                $this->getType()
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
            throw new InvalidPropertyException(
                sprintf(
                    'Unable to set property “%s” on resource type “%s”',
                    $key,
                    $this->getType()
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
    // {{{ public function decode()

    public function decode(array $data)
    {
        $this->checkStore();

        if ($data['type'] !== $this->getType()) {
            throw new InvalidResourceTypeException(
                sprintf(
                    'Resource type %s provided does not match expected type %s',
                    $data['type'],
                    $this->getType()
                )
            );
        }

        $this->id = $data['id'];
        $this->attributes = array_merge($this->attributes, $data['attributes']);

        if (array_key_exists('relationships', $data)) {
            foreach ($data['relationships'] as $key => $resources) {
                if ($resources['data'] === null) {
                    $this->relationships[$key] = null;
                } elseif (array_key_exists('id', $resources['data'])) {
                    $resource = $resources['data'];

                    $identifier = new ResourceIdentifier();
                    $identifier->setStore($this->store);
                    $identifier->decode($resource);

                    $this->relationships[$key] = new ToOneRelationship(
                        $identifier
                    );
                } elseif (is_array($resources['data'])) {
                    $collection = null;
                    foreach ($resources['data'] as $resource) {
                        if (!$collection instanceof ResourceCollection) {
                            $collection = new ResourceCollection(
                                $resource['type']
                            );

                            $collection->setStore($this->store);
                        }

                        $identifier = new ResourceIdentifier();
                        $identifier->setStore($this->store);
                        $identifier->decode($resource);

                        $collection->add($identifier);
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
    // {{{ public function save()

    public function save()
    {
        $this->checkStore();

        return $this->store->save($this);
    }

    // }}}
    // {{{ protected function initAttribute()

    protected function initAttribute($name, $default_value)
    {
        $this->attributes[$name] = $default_value;
    }

    // }}}
    // {{{ protected function initRelationship()

    protected function initRelationship()
    {
    }

    // }}}
    // {{{ abstract protected function initAttributes()

    abstract protected function initAttributes();

    // }}}
    // {{{ abstract protected function initRelationships()

    abstract protected function initRelationships();

    // }}}

    // Serializable interface
    // {{{ public function serialize()

    public function serialize()
    {
        return serialize([
            'type' => $this->getType(),
            'id' => $this->id,
            'attributes' => $this->attributes,
            'relationships' => $this->relationships,
        ]);
    }

    // }}}
    // {{{ public function unserialize()

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        $this->id = $data['id'];
        $this->attributes = $data['attributes'];
        $this->relationships = $data['relationships'];
    }

    // }}}
}
