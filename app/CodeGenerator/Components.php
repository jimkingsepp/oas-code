<?php

namespace App\CodeGenerator;

use App\CodeGenerator\Collection;

class Components
{
	private $schemas;
	/** TODO */
	// private $responses;
	// private $parameters;
	// private $examples;
	// private $requestBodies;
	// private $headers;
	// private $securitySchemes;
	// private $links;
	// private $callbacks;

	public function __construct(array $component_data)
	{
		// var_dump($component_data['schemas']); exit;
		if (isset($component_data['schemas'])) {
			$this->schemas = new Collection;
			array_walk($component_data['schemas'], function ($value, $key) {
				$this->schemas->add(new Schema($value, $key));
			});
		}
	}

	/**
	 * Get the value of schemas.
	 */
	public function getSchemas() : Collection
	{
		return $this->schemas;
	}

	public function getSchemaProperties(string $schema) : array
	{
		$properties = [];

		$schema = $this->schemas->{$schema};
		if (isset($schema['allOf'])) {
			$properties = $schema['allOf']['properties'];
			$ref        = $schema['allOf']['$ref'];
			array_push($properties, str_replace('#/components/schemas/', '', $ref));
			array_merge($properties, $this->getSchemaProperties($allOf_schema));
		}
		$properties = $schema['properties'];

		return $properties;
	}
}

class Schema implements Collectable
{
	private $name;
	private $type;
	private $required;
	private $properties;
	private $schemas = [];

	public function __construct(array $schema_data, string $name)
	{
		$this->name = $name;
		if (array_key_first($schema_data) === 'allOf') {
			array_walk($schema_data['allOf'], function ($item, $key) {
				if (in_array('$ref', array_keys($item))) {
					$this->schemas[] = $item['$ref'];
				} else {
					$this->setMemberVariables($item);
				}
			});
		} else {
			$this->setMemberVariables($schema_data);
		}
	}

	private function setMemberVariables($data)
	{
		$this->type       = $data['type'];
		$this->required   = $data['required'];
		$this->properties = new Collection;
		array_walk($data['properties'], function ($value, $key) {
			$property = new Property($value, $key);
			$this->properties->add($property);
		});
	}

	public function getName() : string
	{
		return $this->name;
    }

    public function makeClass(string $file) : void
    {
        echo json_encode($this->toArray(), JSON_PRETTY_PRINT) . "\n";
    }

	public function getProperties() : Collection
	{
		return $this->properties;
	}

	public function toArray() : array
	{
		$schema_array = ['name' => $this->name];
		iterator_apply($this->properties, function () use (&$schema_array) {
			array_push($schema_array, $this->properties->toArray());
		}, [$this->properties, &$schema_array]);

		return $schema_array;
	}
}
