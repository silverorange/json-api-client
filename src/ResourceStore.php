<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

namespace silverorange\JsonApiClient;

use silverorange\JsonApiClient\Exception\ClassNotFoundException;
use silverorange\JsonApiClient\Exception\InvalidDataException;
use silverorange\JsonApiClient\Exception\InvalidJsonException;
use silverorange\JsonApiClient\Exception\ResourceErrorException;
use silverorange\JsonApiClient\Exception\ResourceNotFoundException;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

class ResourceStore
{
    // {{{ protected properties

    protected $json_api_base = null;
    protected $json_api_headers = null;

    protected $http_client = null;

    protected $class_by_type = [];

    protected $resources = [];

    protected $is_to_many_replace_enabled = true;

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
        $body = $this->doRequest(
            'GET',
            $this->getResourceAddress($type),
            ['query' => $query_params]
        );

        if (!isset($body['data'])) {
            throw new InvalidDataException(
                'Find collection response is missing required "data" field.',
                0,
                $body
            );
        }

        $collection = new ResourceCollection($type);
        $collection->setStore($this);
        $collection->decode($body['data']);

        foreach ($collection as $resource) {
            if (isset($body['meta']['timeStamp'])) {
                $resource->setFetchedDate($body['meta']['timeStamp']);
            }

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

    /**
     * Finds a resource either from the cache or if not present in the cache,
     * from the API server.
     *
     * @param string $type         the resource type to find.
     * @param string $id           the resource identifier.
     * @param array  $query_params optional array of extra parameters.
     *
     * @return Resource|null the resource object or null if no such resource
     *         could be found.
     */
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

    /**
     * Finds a resource from the API server.
     *
     * @param string $type         the resource type to find.
     * @param string $id           the resource identifier.
     * @param array  $query_params optional array of extra parameters.
     *
     * @return Resource|null the resource object or null if no such resource
     *         could be found.
     */
    public function query($type, $id, array $query_params = [])
    {
        $resource = null;

        try {
            $class = $this->getClass($type);

            $body = $this->doRequest(
                'GET',
                $this->getResourceAddress($type, $id),
                ['query' => $query_params]
            );

            if (!isset($body['data'])) {
                throw new InvalidDataException(
                    'Find resource response is missing required "data" field.',
                    0,
                    $body
                );
            }

            $resource = new $class();
            $resource->setStore($this);
            $resource->decode($body['data']);

            if (isset($body['meta']['timeStamp'])) {
                $resource->setFetchedDate($body['meta']['timeStamp']);
            }

            $this->setResource($type, $id, $resource);
        } catch (ResourceNotFoundException $e) {
            // not found is non-fatal for query method
        }

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

    /**
     * Finds a resource from the cache.
     *
     * @param string $type         the resource type to find.
     * @param string $id           the resource identifier.
     * @param array  $query_params optional array of extra parameters.
     *
     * @return Resource|null the resource object or null if no such resource
     *         could be found.
     */
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
        $method = 'POST';
        $options = [];

        // Check if we are updating an existing resource.
        if ($resource->isSaved()) {
            $method = 'PATCH';
            $options['is_to_many_replace_enabled'] = $this->is_to_many_replace_enabled;
        }

        $body = $this->doRequest(
            $method,
            $this->getResourceAddress(
                $resource->getType(),
                $resource->getId()
            ),
            ['json' => $resource->encode($options)]
        );

        if (!isset($body['data'])) {
            throw new InvalidDataException(
                'Save resource response is missing required "data" field.',
                0,
                $body
            );
        }

        $resource->setStore($this);
        $resource->decode($body['data']);

        if (isset($body['meta']['timeStamp'])) {
            $resource->setFetchedDate($body['meta']['timeStamp']);
        }

        return $resource;
    }

    // }}}
    // {{{ public function delete()

    public function delete(Resource $resource)
    {
        if ($resource->isSaved()) {
            $this->doRequest(
                'DELETE',
                $this->getResourceAddress(
                    $resource->getType(),
                    $resource->getId()
                ),
                []
            );
        }
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
    // {{{ public function enableToManyReplace()

    public function enableToManyReplace()
    {
        $this->is_to_many_replace_enabled = true;
    }

    // }}}
    // {{{ public function disableToManyReplace()

    public function disableToManyReplace()
    {
        $this->is_to_many_replace_enabled = false;
    }

    // }}}
    // {{{ protected function doRequest()

    protected function doRequest($method, $url, array $params)
    {
        try {
            $response = $this->http_client->request(
                $method,
                $url,
                $params
            );
        } catch (ClientException $e) {
            $response = $e->getResponse();
        } catch (ServerException $e) {
            $response = $e->getResponse();
        }

        if ($response->getStatusCode() === 204) {
            $body = null;
        } else {
            $body = $response->getBody();
            $body = json_decode($body, true);

            $this->validateTopLevelJsonResponse($body);

            if (isset($body['errors'])) {
                $this->handleTopLevelErrorResponse($body);
            }
        }

        return $body;
    }

    // }}}
    // {{{ protected function hasResource()

    protected function hasResource($type, $id)
    {
        return (
            $this->hasResources($type) &&
            isset($this->resources[$type][$id])
        );
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
    // {{{ protected function validateTopLevelJsonResponse()

    protected function validateTopLevelJsonResponse($body)
    {
        if (!is_array($body)) {
            throw new InvalidJsonException('Invalid JSON received.');
        }

        if (!isset($body['data']) &&
            !isset($body['errors']) &&
            !isset($body['meta'])
        ) {
            throw new InvalidDataException(
                'Response is missing required top-level "data", "errors" or "meta" field.',
                0,
                $body
            );
        }

        if (isset($body['data']) && isset($body['errors'])) {
            throw new InvalidDataException(
                'Response can not contain both a top-level "data" and top-level "errors" field.',
                0,
                $body
            );
        }
    }

    // }}}
    // {{{ protected function handleTopLevelErrorResponse()

    protected function handleTopLevelErrorResponse(array $body)
    {
        if (count($body['errors']) > 0) {
            $error = $body['errors'][0];
            if (isset($error['status']) && $error['status'] === '404') {
                throw new ResourceNotFoundException('', 0, $error);
            }
            throw new ResourceErrorException('', 0, $error);
        }

        throw new ResourceErrorException('Unknown error.', 0, []);
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
