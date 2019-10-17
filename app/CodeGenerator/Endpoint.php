<?php

namespace App\CodeGenerator;

class Endpoint implements Collectable
{
	private $path;
	private $method;
	private $summary;
	private $operationId;
	private $parameters;
	private $request_body;
	private $responses;
	private $deprecated;

	public function __construct(string $path, string $method, \stdClass $data)
	{
		$this->path = $path;
		$this->method = $method;
		$this->summary = $data->summary;
		$this->description = $data->description;
		$this->operationId = $data->operationId;
		$this->parameters = $data->parameters;
        $this->request_body = isset($data->requestBody) ? new RequestBody($data->requestBody) : null;
        // TODO: add Response class
		// $this->responses = isset($data->responses) ? new Responses($data->responses) : null;
	}

	public function getPath() : string
	{
		return $this->path;
	}

	public function getMethod() : string
	{
		return $this->method;
	}

	public function getOperationId() : string
	{
		return $this->operationId;
	}

	public function toArray() : array
	{
        $return_array = get_object_vars($this);
        $return_array['request_body'] = $this->getRequestBodyData();

        return $return_array;
	}

	private function getRequestBody() : ?RequestBody
	{
		return $this->request_body;
	}

	public function getRequestBodyData() : array
	{
		return $this->getRequestBody() ? $this->getRequestBody()->toArray() : [];
	}

	public function getRequestBodyContent() : array
	{
		return $this->getRequestBody() ? $this->getRequestBody()->getContent()->toArray() : [];
	}
}
