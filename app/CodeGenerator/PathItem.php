<?php

namespace App\CodeGenerator;

class PathItem implements Collectable
{
	private $path;      // relative path to an individual endpoint
	private $operation; //  operation (i.e., get, put, post, etc.)
	/** Per Open API 3.0 spec, the following are fields of an operation object */
	private $tags;
	private $summary;
	private $description;
	private $externalDocs;
	private $operationId;
	private $parameters;
	private $request_body;
	private $responses;
	private $callbacks;
	private $deprecated;
	private $servers;

	public function __construct(string $path, string $operation, array $data)
	{
		$this->path = $path;
		$this->operation = $operation;

		$member_variables = array_keys(get_object_vars($this));
		array_map(function ($value) use ($data) {
			if (isset($data[$value])) {
				$this->$value = $data[$value];
			}
		}, $member_variables);
	}

	public function getPath() : string
	{
		return $this->path;
	}

	public function getOperation() : string
	{
		return $this->operation;
	}

	public function getOperationId() : string
	{
		return str_replace(' ', '', ucwords($this->operationId));
	}

	public function toArray() : array
	{
		return get_object_vars($this);
	}
}
