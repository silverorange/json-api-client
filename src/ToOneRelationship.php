<?php

namespace silverorange;

class ToOneRelationship
{
	// {{{ protected properties

	protected $type;
	protected $id;
	protected $pointer;

	// }}}
	// {{{ public function __construct()

	public function __construct(ResourcePointer $pointer)
	{
		$this->type = $pointer->getType();
		$this->id = $pointer->getId();
		$this->pointer = $pointer;
	}

	// }}}
	// {{{ public function getId()

	public function getId()
	{
		return $this->id;
	}

	// }}}
	// {{{ public function getType()

	public function getType()
	{
		return $this->type;
	}

	// }}}
	// {{{ public function get()

	public function get()
	{
		return $this->pointer;
	}

	// }}}
	// {{{ public function set()

	public function set(ResourcePointer $pointer)
	{
		$this->type = $pointer->getType();
		$this->id = $pointer->getId();
		$this->pointer = $pointer;
	}

	// }}}
}
