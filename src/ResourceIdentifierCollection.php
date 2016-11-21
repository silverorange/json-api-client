<?php

namespace silverorange;

class ResourceIdentifierCollection extends \ArrayObject
{
    protected $type;

    public function __construct($type)
    {
        parent::__construct();

        $this->type = $type;
    }

    public function append(ResourceIdentifier $resource)
    {
        $this[$resource->getId()] = $resource;
    }

    public function offsetSet($offest, $value)
    {
        if (!$value instanceof ResourceIdentifier) {
            throw new \Exception(
                'Only ResourceIdetifiers may be added to this collection'
            );
        }

        if ($value->getType() != $this->type) {
            throw new \Exception(
                sprintf(
                    'Unable to add resource of type “%s” to '.
                    'collection of type “%s”',
                    $valuye->getType(),
                    $this->type
                )
            );
        }

        parent::offsetSet($offest, $value);
    }

    public function offsetGet($offset)
    {
        $resource = parent::offsetGet($offset);

        if ($resource instanceof ResourceIdentifier) {
            $resource = $resource->getResource();
            $this[$resource->getId()] = $resource;
        }

        return $resource;
    }
}
