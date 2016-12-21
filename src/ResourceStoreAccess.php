<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */

namespace silverorange\JsonApiClient;

interface ResourceStoreAccess
{
    public function setStore(ResourceStore $store);
}
