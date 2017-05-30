JSON API Client
===============
A PHP library for working with JSON API servers.

The following features are supported:

 - loading resource collections
 - loading individual resources by identifier
 - creating resources
 - saving resources
 - to one relationships
 - to many relationships

The `ResourceStore` is responsible for performing HTTP requests and caching
resources. The following APIs are provided:

 - `peek()` - look for a cached resource without hitting the backend server
 - `query()` - look for a resource while always hitting the backend server
 - `find()` - look for a resource using cache if available, hit the backend if the cached resource is not available
 - `create()` - make a new resoure
 - `save()` - save a resource
 - `delete()` - delete a resource

The Guzzle HTTP library is used to make requests.

Installation
------------
Make sure the silverorange composer repository is added to the `composer.json`
for the project and then run:

```sh
composer require silverorange/json-api-client
```
