<?php

namespace silverorange\JsonApiClient;

abstract class Resource extends ResourceIdentifier implements ResourceStoreAccess
{
    // {{{ protected properties

    protected $attributes = [];
    protected $to_one_relationships = [];
    protected $to_many_relationships = [];

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

        foreach ($this->to_one_relationships as $key => $relationship) {
            if ($relationship instanceof ResourceStoreAccess) {
                $relationship->setStore($store);
            }
        }

        foreach ($this->to_many_relationships as $key => $relationship) {
            if ($relationship instanceof ResourceStoreAccess) {
                $relationship->setStore($store);
            }
        }
    }

    // }}}
    // {{{ public function get()

    public function get($key)
    {
        if ($this->hasToOneRelationship($key)) {
            return $this->getToOneRelationship($key)->get();
        }

        if ($this->hasToManyRelationship($key)) {
            return $this->getToManyRelationship($key)->get();
        }

        if ($this->hasAttribute($key)) {
            return $this->getAttribute($key);
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

    public function set($name, $value)
    {
        if ($this->hasRelationship($name)) {
            $this->setRelationship($name, $value);
        } elseif ($this->hasAttribute($name)) {
            $this->setAttribute($name, $value);
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

        foreach ($this->to_one_relationships as $name => $relationship) {
            $relationships[$name] = $relationship->encodeIdentifier();
        }

        foreach ($this->to_many_relationships as $name => $relationship) {
            $relationships[$name] = $relationship->encodeIdentifier();
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
                    'Resource type “%s” provided does not match expected type “%s”',
                    $data['type'],
                    $this->getType()
                )
            );
        }

        $this->id = $data['id'];

        foreach ($data['attributes'] as $name => $value) {
            $this->setAttribute($name, $value);
        }

        if (array_key_exists('relationships', $data)) {
            foreach ($data['relationships'] as $key => $resources) {
                if ($resources['data'] !== null) {
                    if ($this->hasToOneRelationship($key)) {
                        $relationship = $this->to_one_relationships[$key];

                        $identifier = new ResourceIdentifier();
                        $identifier->setStore($this->store);
                        $identifier->decode($resources['data']);

                        $relationship->set($identifier);
                    } elseif ($this->hasToManyRelationship($key)) {
                        $relationship = $this->to_many_relationships[$key];

                        $collection = new ResourceCollection(
                            $relationship->getType()
                        );

                        $collection->setStore($this->store);

                        foreach ($resources['data'] as $resource) {
                            $identifier = new ResourceIdentifier();
                            $identifier->setStore($this->store);
                            $identifier->decode($resource);

                            $collection->add($identifier);
                        }

                        $relationship->set($collection);
                    } else {
                        throw new InvalidPropertyException(
                            sprintf(
                                'Unable to set relationship “%s” on resource type “%s”',
                                $key,
                                $this->getType()
                            )
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

    // Attribute methods
    // {{{ protected function hasAttribute()

    protected function hasAttribute($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    // }}}
    // {{{ protected function setAttribute()

    protected function setAttribute($name, $value)
    {
        if ($this->hasAttribute($name)) {
            $this->attributes[$name] = $value;
        } else {
            throw new InvalidPropertyException(
                sprintf(
                    'Unable to set attribute “%s” on resource type “%s”',
                    $name,
                    $this->getType()
                )
            );
        }
    }

    // }}}
    // {{{ protected function getAttribute()

    protected function getAttribute($name)
    {
        if ($this->hasAttribute($name)) {
            return $this->attributes[$name];
        } else {
            throw new InvalidPropertyException(
                sprintf(
                    'Unable to get attribute “%s” on resource type “%s”',
                    $name,
                    $this->getType()
                )
            );
        }
    }

    // }}}
    // {{{ protected function initAttribute()

    protected function initAttribute($name, $default_value)
    {
        $this->attributes[$name] = $default_value;
    }

    // }}}
    // {{{ abstract protected function initAttributes()

    abstract protected function initAttributes();

    // }}}

    // Relationship methods
    // {{{ protected function hasRelationship()

    protected function hasRelationship($name)
    {
        return (
            $this->hasToOneRelationship($name) ||
            $this->hasToManyRelationship($name)
        );
    }

    // }}}
    // {{{ protected function hasToOneRelationship()

    protected function hasToOneRelationship($name)
    {
        return array_key_exists($name, $this->to_one_relationships);
    }

    // }}}
    // {{{ protected function hasToManyRelationship()

    protected function hasToManyRelationship($name)
    {
        return array_key_exists($name, $this->to_many_relationships);
    }

    // }}}
    // {{{ protected function setRelationship()

    protected function setRelationship($name, $value)
    {
        if ($this->hasToOneRelationship($name)) {
            $this->setToOneRelationship($name, $value);
        } elseif ($this->hasToManyRelationship($name)) {
            $this->setToManyRelationship($name, $value);
        } else {
            throw new InvalidPropertyException(
                sprintf(
                    'Unable to set relationship “%s” on resource type “%s”',
                    $key,
                    $this->getType()
                )
            );
        }
    }

    // }}}
    // {{{ protected function setToOneRelationship()

    protected function setToOneRelationship($name, Resource $value)
    {
        if ($this->hasToOneRelationship($name)) {
            $this->getToOneRelationship($name)->set($value);
        } else {
            throw new InvalidPropertyException(
                sprintf(
                    'Unable to set relationship “%s” on resource type “%s”',
                    $key,
                    $this->getType()
                )
            );
        }
    }

    // }}}
    // {{{ protected function setToManyRelationship()

    protected function setToManyRelationship($name, ResourceCollection $value)
    {
        if ($this->hasToManyRelationship($name)) {
            $this->getToManyRelationship($name)->set($value);
        } else {
            throw new InvalidPropertyException(
                sprintf(
                    'Unable to set relationship “%s” on resource type “%s”',
                    $key,
                    $this->getType()
                )
            );
        }
    }

    // }}}
    // {{{ protected function getToOneRelationship()

    protected function getToOneRelationship($name)
    {
        if ($this->hasToOneRelationship($name)) {
            return $this->to_one_relationships[$name];
        } else {
            throw new InvalidPropertyException(
                sprintf(
                    'Unable to get relationship “%s” on resource type “%s”',
                    $key,
                    $this->getType()
                )
            );
        }
    }

    // }}}
    // {{{ protected function getToManyRelationship()

    protected function getToManyRelationship($name)
    {
        if ($this->hasToManyRelationship($name)) {
            return $this->to_many_relationships[$name];
        } else {
            throw new InvalidPropertyException(
                sprintf(
                    'Unable to get relationship “%s” on resource type “%s”',
                    $key,
                    $this->getType()
                )
            );
        }
    }

    // }}}
    // {{{ protected function initToOneRelationship()

    protected function initToOneRelationship($name, $type)
    {
        $this->to_one_relationships[$name] = new ToOneRelationship($type);
    }

    // }}}
    // {{{ protected function initToManyRelationship()

    protected function initToManyRelationship($name, $type)
    {
        $this->to_many_relationships[$name] = new ToManyRelationship($type);
    }

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
            'to_one_relationships' => $this->to_one_relationships,
            'to_many_relationships' => $this->to_many_relationships,
        ]);
    }

    // }}}
    // {{{ public function unserialize()

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        $this->id = $data['id'];
        $this->attributes = $data['attributes'];
        $this->to_one_relationships = $data['to_one_relationships'];
        $this->to_many_relationships = $data['to_many_relationships'];
    }

    // }}}
}
