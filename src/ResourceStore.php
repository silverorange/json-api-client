<?php

namespace silverorange;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as HttpClient;

class ResourceStore
{
    // {{{ protected properties

    protected $json_api_base;
    protected $token;
    protected $client;

    protected $class_by_type = array();

    protected $resources = array();

    // }}}
    // {{{ public function setJsonApiBase()

    public function setJsonApiBase($json_api_base)
    {
        $this->json_api_base = $json_api_base;
    }

    // }}}
    // {{{ public function setToken()

    public function setToken($token)
    {
        $this->token = $token;
        $this->client = new HttpClient(
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/vnd.api+json',
                    'Content-Type' => 'application/vnd.api+json',
                ]
            ]
        );
    }

    // }}}
    // {{{ public function getClient()

    public function getClient()
    {
        return $this->client;
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

    public function findAll($type, $query_params = [])
    {
        $result = $this->getClient()->request(
            'GET',
            $this->getResourceAddress($type),
            ['query' => $query_params]
        );

        $body = json_decode($result->getBody(), true);

        $collection = new ResourceCollection($this, $type);
        $collection->decode($body['data']);

        foreach ($collection as $resource) {
            $this->setResource(
                $resource->getType(),
                $resource->getId(),
                $resource
            );
        }

        return $collection;
    }

    // }}}
    // {{{ public function find()

    public function find($type, $id, $query_params = [])
    {
        if (!$this->hasResource($type, $id)) {
            $result = $this->getClient()->request(
                'GET',
                $this->getResourceAddress($type, $id),
                ['query' => $query_params]
            );

            $body = json_decode($result->getBody(), true);

            $resource = new Resource($this);
            $resource->decode($body['data']);

            $this->setResource($type, $id, $resource);
        }

        return $this->getResource($type, $id);
    }

    // }}}
    // {{{ public function save()

    public function save(Resource $resource)
    {
        $result = $this->getClient()->request(
            'PATCH',
            $this->getResourceAddress(
                $resource->getType(),
                $resource->getId()
            ),
            ['body' => $resource->encode()]
        );

        $body = json_decode($result->getBody(), true);

        $resource = new Resource($this);
        $resource->decode($body['data']);

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
    // {{{ public function create()

    public function create(Resource $resource)
    {
        $result = $this->getClient()->request(
            'POST',
            $this->getResourceAddress($resource->getType()),
            ['body' => $resource->encode()]
        );

        $body = json_decode($result->getBody(), true);

        $resource = new Resource($this);
        $resource->decode($body['data']);

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
    // {{{ public function __sleep()

    public function __sleep()
    {
        return array('json_api_base', 'token');
    }

    // }}}
}
