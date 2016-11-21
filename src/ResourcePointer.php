<?php

namespace silverorange;

class ResourcePointer
{
	// {{{ protected properties

	protected $type;
	protected $id;
	protected $store;
	protected $resource;

	// }}}
	// {{{ public function __construct()

	public function __construct(ResourceStore $store, $type, $id)
	{
		$this->store = $store;
		$this->type = $type;
		$this->id = $id;
	}

	// }}}
	// {{{ public function getType()

	public function getType()
	{
		return $this->type;
	}

	// }}}
	// {{{ public function getId()

	public function getId()
	{
		return $this->id;
	}

	// }}}
	// {{{ public function get()

	public function get($key)
	{
		return $this->getResource()->get($key);
	}

	// }}}
	// {{{ public function set()

	public function set($key, $value)
	{
		$this->getResource()->set($key, $value);
	}

	// }}}
	// {{{ public function getResource()

	public function getResource()
	{
		if (!$this->resource instanceof Resource) {
			$this->resource = $this->store->findResource(
				$this->getType(),
				$this->getId()
			);
		}

		return $this->resource;
	}

	// }}}
}
