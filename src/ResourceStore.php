<?php

namespace silverorange;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as HttpClient;

class ResourceStore
{
    // {{{ protected properties

    protected $json_api_base = null;
    protected $json_api_headers = null;

    protected $http_client = null;

    protected $class_by_type = array();

    protected $resources = array();

    // }}}
    // {{{ public function __construct()

    public function __construct($json_api_base, $json_api_headers = [])
    {
        $this->json_api_base = $json_api_base;
        $this->json_api_headers = $json_api_headers;

        $this->initHttpClient();
    }

    // }}}
    // {{{ protected function initHttpClient()

    protected function initHttpClient()
    {
        $default_headers = $this->getDefaultHttpHeaders();

        $this->http_client = new HttpClient(
            [
                'base_uri' => $this->json_api_base,
                'headers' => array_merge(
                    $default_headers,
                    $this->json_api_headers
                ),
            ]
        );
    }

    // }}}
    // {{{ protected function getDefaultHttpHeaders()

    protected function getDefaultHttpHeaders()
    {
        return [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ];
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
        $result = $this->http_client->request(
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
            $result = $this->http_client->request(
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
        $result = $this->http_client->request(
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
        $result = $this->http_client->request(
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
        return ($id != '') ? $type . '/' . $id : $type;
    }

    // }}}
    // {{{ public function __sleep()

    public function __sleep()
    {
        return array('json_api_base', 'json_api_headers');
    }

    // }}}
    // {{{ public function __wakeup()

    public function __wakeup()
    {
        $this->initHttpClient();
    }

    // }}}
}
