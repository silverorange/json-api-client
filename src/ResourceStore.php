<?php

namespace silverorange;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as HttpClient;

class ResourceStore
{
    // {{{ protected properties

    protected $json_api_base;

    protected $class_by_type = array();

    protected $resources = array();

    // }}}
    // {{{ public function __construct()

    public function __construct()
    {
        $this->client = new HttpClient(
            [
                'headers' => [
                    'Authorization' => 'Bearer 9f8a2c79-18ed-4053-8f3b-ae5264109955',
                    'Accept' => 'application/vnd.api+json',
                ]
            ]
        );
    }

    // }}}
    // {{{ public function setJsonApiBase()

    public function setJsonApiBase($json_api_base)
    {
        $this->json_api_base = $json_api_base;
    }

    // }}}
    // {{{ public function addClass()

    public function addClass($type, $class)
    {
        $this->class_by_type[$type] = $class;
    }

    // }}}
    // {{{ public function getClass()

    public function getClass($type)
    {
        return $this->class_by_type[$type];
    }

    // }}}
    // {{{ public function findAll()

    public function findAll($type)
    {
        $request = new Request(
            'GET',
            $this->getResourceAddress($type)
        );

        $result = $this->client->send($request);

        $body = json_decode($result->getBody(), true);

        $collection = new ResourceIdentifierCollection($type);
        foreach ($body['data'] as $data) {
            $resource = new Resource($this);
            $resource->decode(json_encode(['data' => $data]));

            $this->setResource(
                $resource->getType(),
                $resource->getId(),
                $resource
            );

            $collection->append($resource);
        }

        return $collection;
    }

    // }}}
    // {{{ public function find()

    public function find($type, $id)
    {
        if (!$this->hasResource($type, $id)) {
            $request = new Request(
                'GET',
                $this->getResourceAddress($type, $id)
            );

            $result = $this->client->send($request);

            $resource = new Resource($this);
            $resource->decode($result->getBody());

            $this->setResource($type, $id, $resource);
        }

        return $this->getResource($type, $id);
    }

    // }}}
    // {{{ public function save()

    public function save(Resource $resource)
    {
        $request = new Request(
            'PATCH',
            $this->getResourceAddress(
                $resource->getType(),
                $resource->getId()
            ),
            ['Content-Type' => 'application/vnd.api+json'],
            $resource->encode()
        );

        $result = $this->client->send($request);

        $resource = new Resource($this);
        $resource->decode($result->getBody());

        // Replace the old resource with a new one
        $this->setResource(
            $resource->getType(),
            $resource->getId(),
            $resource
        );

        return $this->getResource(
            $resource->getType(),
            $resource->getId()
        );
    }

    // }}}
    // {{{ protected function hasResource()

    protected function hasResource($type, $id)
    {
        return isset($this->resources[$type][$id]);
    }

    // }}}
    // {{{ protected function getResource()

    protected function getResource($type, $id)
    {
        return $this->resources[$type][$id];
    }

    // }}}
    // {{{ protected function setResource()

    protected function setResource($type, $id, Resource $resource)
    {
        return $this->resources[$type][$id] = $resource;
    }

    // }}}
    // {{{ protected function getResourceAddress()

    protected function getResourceAddress($type, $id = null)
    {
        if ($id != '') {
            return sprintf(
                '%s/%s/%s',
                $this->json_api_base,
                $type,
                $id
            );
        } else {
            return sprintf(
                '%s/%s',
                $this->json_api_base,
                $type
            );
        }
    }

    // }}}
}
