<?php

namespace App\CodeGenerator;

class Components
{
	private $schemas;
	private $responses;
	private $parameters;
	private $examples;
	private $requestBodies;
	private $headers;
	private $securitySchemes;
	private $links;
	private $callbacks;

	public function __construct(array $data)
	{
		$member_variables = array_keys(get_object_vars($this));
		array_map(function ($value) use ($data) {
			if (isset($data[$value])) {
				$this->$value = $data[$value];
			}
		}, $member_variables);
	}

	// public function toArray() : array
	// {
	//     return [
	//         'properties' => $this->property_collection->toArray()
	//     ];
	// }

	/**
	 * Get the value of schemas
	 */
	public function getSchemas()
	{
		return $this->schemas;
	}
}
