<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

namespace silverorange\JsonApiClient;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as HttpClient;

class ResourceStore
{
    // {{{ protected properties

    protected $json_api_base = null;
    protected $json_api_headers = null;

    protected $http_client = null;

    protected $class_by_type = [];

    protected $resources = [];

    // }}}
    // {{{ public function __construct()

    public function __construct($json_api_base, array $json_api_headers = [])
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
    // {{{ public function findAll()

    public function findAll($type, array $query_params = [])
    {
        $result = $this->http_client->request(
            'GET',
            $this->getResourceAddress($type),
            ['query' => $query_params]
        );

        $body = json_decode($result->getBody(), true);

        $this->checkJsonBody($body);

        $collection = new ResourceCollection($type);
        $collection->setStore($this);
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

    public function find($type, $id, array $query_params = [])
    {
        $resource = $this->peek($type, $id);

        if (!$resource instanceof Resource) {
            $resource = $this->query($type, $id, $query_params);
        }

        return $resource;
    }

    // }}}
    // {{{ public function query()

    public function query($type, $id, array $query_params = [])
    {
        $result = $this->http_client->request(
            'GET',
            $this->getResourceAddress($type, $id),
            ['query' => $query_params]
        );

        $body = json_decode($result->getBody(), true);

        $this->checkJsonBody($body);

        $class = $this->getClass($type);

        $resource = new $class();
        $resource->setStore($this);
        $resource->decode($body['data']);

        $this->setResource($type, $id, $resource);

        return $resource;
    }

    // }}}
    // {{{ public function peekAll()

    public function peekAll($type)
    {
        $collection = new ResourceCollection($type);
        $collection->setStore($this);

        if ($this->hasResources($type)) {
            foreach ($this->getResources($type) as $resource) {
                $collection->add($resource);
            }
        }

        return $collection;
    }

    // }}}
    // {{{ public function peek()

    public function peek($type, $id)
    {
        $resource = null;

        if ($this->hasResource($type, $id)) {
            $resource = $this->getResource($type, $id);
        }

        return $resource;
    }

    // }}}
    // {{{ public function save()

    public function save(Resource $resource)
    {
        $method = ($resource->getId() == '') ? 'POST' : 'PATCH';

        $result = $this->http_client->request(
            $method,
            $this->getResourceAddress(
                $resource->getType(),
                $resource->getId()
            ),
            ['json' => $resource->encode()]
        );

        $body = json_decode($result->getBody(), true);

        $this->checkJsonBody($body);

        $resource->setStore($this);
        $resource->decode($body['data']);

        return $resource;
    }

    // }}}
    // {{{ public function create()

    public function create($type)
    {
        $class = $this->getClass($type);

        $resource = new $class();
        $resource->setStore($this);

        return $resource;
    }

    // }}}
    // {{{ protected function hasResource()

    protected function hasResource($type, $id)
    {
        return isset($this->resources[$type][$id]);
    }

    // }}}
    // {{{ protected function hasResources()

    protected function hasResources($type)
    {
        return isset($this->resources[$type]);
    }

    // }}}
    // {{{ protected function getResource()

    protected function getResource($type, $id)
    {
        return $this->resources[$type][$id];
    }

    // }}}
    // {{{ protected function getResources()

    protected function getResources($type)
    {
        return $this->resources[$type];
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
    // {{{ protected function checkJsonBody()

    protected function checkJsonBody($body)
    {
        if (!is_array($body) || !isset($body['data'])) {
            throw InvalidJsonException('Invalid JSON received.');
        }
    }

    // }}}

    // class methods
    // {{{ public function addClass()

    public function addClass($type, $class)
    {
        $this->class_by_type[$type] = $class;
    }

    // }}}
    // {{{ public function hasClass()

    public function hasClass($type)
    {
        return array_key_exists($type, $this->class_by_type);
    }

    // }}}
    // {{{ public function getClass()

    public function getClass($type)
    {
        if ($this->hasClass($type)) {
            return $this->class_by_type[$type];
        }

        throw new ClassNotFoundException(
            sprintf('No class for type “%s” defined.', $type)
        );
    }

    // }}}
}
