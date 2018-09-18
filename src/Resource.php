<?php

namespace silverorange\JsonApiClient;

use silverorange\JsonApiClient\Exception\InvalidPropertyException;
use silverorange\JsonApiClient\Exception\InvalidResourceTypeException;

abstract class Resource extends AbstractResource
{
    // {{{ class constants

    const TYPE_STRING = 0;
    const TYPE_NUMBER = 1;
    const TYPE_DATE = 2;

    const AUTO_SAVE_ON = true;
    const AUTO_SAVE_OFF = false;

    // }}}
    // {{{ protected properties

    protected $attributes = [];
    protected $attributes_types = [];
    protected $to_one_relationships = [];
    protected $to_many_relationships = [];
    protected $auto_save_relationships = [];
    protected $fetched_date = null;
    protected $is_modified = true;

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
        // Prevent never ending recursion if the store is already set
        if (!$this->store instanceof ResourceStore) {
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
    }

    // }}}
    // {{{ public function setFetchedDate()

    public function setFetchedDate($fetched_date)
    {
        if (is_string($fetched_date)) {
            $this->fetched_date = $this->decodeDate($fetched_date);
        }
    }

    // }}}
    // {{{ public function getFetchedDate()

    public function getFetchedDate()
    {
        return $this->fetched_date;
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
                    $name,
                    $this->getType()
                )
            );
        }
    }

    // }}}
    // {{{ public function encode()

    public function encode(array $options = [])
    {
        $is_to_many_replace_enabled = (
            !isset($options['is_to_many_replace_enabled']) ||
            $options['is_to_many_replace_enabled']
        );

        $relationships = [];

        foreach ($this->to_one_relationships as $name => $relationship) {
            if ($relationship->isSelfModified()) {
                $encoded = $relationship->encodeIdentifier($options);
                $relationships[$name] = $encoded;
            }
        }

        if ($is_to_many_replace_enabled) {
            foreach ($this->to_many_relationships as $name => $relationship) {
                $encoded = $relationship->encodeIdentifier($options);
                if ($encoded['data'] !== []) {
                    $relationships[$name] = $encoded;
                }
            }
        }

        $attributes = [];

        foreach ($this->attributes as $name => $value) {
            if ($value instanceof \DateTime) {
                $value = $value->format('c');
            }

            $attributes[$name] = $value;
        }

        $data = [
            'data' => []
        ];

        if ($this->isSaved()) {
            $data['data']['id'] = $this->getId();
        }

        $data['data']['type'] = $this->getType();
        $data['data']['attributes'] = $attributes;
        $data['data']['relationships'] = $relationships;

        return $data;
    }

    // }}}
    // {{{ public function decode()

    public function decode(array $data)
    {
        $this->checkStore();
        $this->validateData($data);

        $this->id = $data['id'];

        if (array_key_exists('attributes', $data)) {
            foreach ($data['attributes'] as $name => $value) {
                $this->setAttribute($name, $value);
            }
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
                    }
                }
            }
        }

        $this->is_modified = false;
    }

    // }}}
    // {{{ public function save()

    public function save()
    {
        $this->checkStore();

        $is_modified = $this->isModified();

        foreach ($this->to_one_relationships as $name => $relationship) {
            if ($this->auto_save_relationships[$name] === self::AUTO_SAVE_ON) {
                $is_modified = $is_modified || $relationship->isModified();
                $relationship->save();
            }
        }

        foreach ($this->to_many_relationships as $name => $relationship) {
            if ($this->auto_save_relationships[$name] === self::AUTO_SAVE_ON) {
                $is_modified = $is_modified || $relationship->isModified();
                $relationship->save();
            }
        }

        if ($is_modified) {
            $this->store->save($this);
            $this->is_modified = false;
        }
    }

    // }}}
    // {{{ public function delete()

    public function delete()
    {
        $this->checkStore();

        $this->store->delete($this);
    }

    // }}}
    // {{{ public function isModified()

    public function isModified()
    {
        return ($this->is_modified || !$this->isSaved());
    }

    // }}}
    // {{{ protected function validateData()

    protected function validateData(array $data)
    {
        parent::validateData($data);

        if ($data['type'] !== $this->getType()) {
            throw new InvalidResourceTypeException(
                sprintf(
                    'Resource type “%s” provided does not match expected type “%s”',
                    $data['type'],
                    $this->getType()
                )
            );
        }
    }

    // }}}
    // {{{ protected function decodeDate()

    protected function decodeDate($date_string)
    {
        return new \DateTime((string)$date_string);
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
            $this->is_modified = true;

            if ($this->attributes_types[$name] === self::TYPE_DATE &&
                is_string($value)) {
                $value = $this->decodeDate($value);
            }

            $this->attributes[$name] = $value;
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

    protected function initAttribute($name, $default_value, $type = self::TYPE_STRING)
    {
        $this->attributes[$name] = $default_value;
        $this->attributes_types[$name] = $type;
    }

    // }}}
    // {{{ abstract protected function initAttributes()

    abstract protected function initAttributes();

    // }}}
    // {{{ public function isSaved()

    public function isSaved()
    {
        return $this->getId() != '';
    }

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

    protected function setToOneRelationship($name, Resource $value = null)
    {
        if ($this->hasToOneRelationship($name)) {
            $this->is_modified = true;

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
            $this->is_modified = true;

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

    protected function initToOneRelationship(
        $name,
        $type,
        $auto_save = self::AUTO_SAVE_OFF
    ) {
        $this->to_one_relationships[$name] = new ToOneRelationship($type);
        $this->auto_save_relationships[$name] = $auto_save;
    }

    // }}}
    // {{{ protected function initToManyRelationship()

    protected function initToManyRelationship(
        $name,
        $type,
        $auto_save = self::AUTO_SAVE_OFF
    ) {
        $this->to_many_relationships[$name] = new ToManyRelationship($type);
        $this->auto_save_relationships[$name] = $auto_save;
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
            'id' => $this->id,
            'attributes' => $this->attributes,
            'attributes_types' => $this->attributes_types,
            'to_one_relationships' => $this->to_one_relationships,
            'to_many_relationships' => $this->to_many_relationships,
            'auto_save_relationships' => $this->auto_save_relationships,
            'fetched_date' => $this->fetched_date,
            'is_modified' => $this->is_modified,
        ]);
    }

    // }}}
    // {{{ public function unserialize()

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        $this->id = $data['id'];
        $this->attributes = $data['attributes'];
        $this->attributes_types = $data['attributes_types'];
        $this->to_one_relationships = $data['to_one_relationships'];
        $this->to_many_relationships = $data['to_many_relationships'];
        $this->auto_save_relationships = $data['auto_save_relationships'];
        $this->fetched_date = $data['fetched_date'];
        $this->is_modified = $data['is_modified'];
    }

    // }}}
}
