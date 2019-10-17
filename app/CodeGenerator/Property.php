<?php

namespace App\CodeGenerator;

class Property implements Collectable
{
	private $name;
	private $type;
	private $description;
	private $example;
	private $format;
	private $minimum;
	private $maximum;
	private $ref;

	public function __construct($name, $data)
	{
		$this->name = $name;
		array_walk($data, function ($value, $key) {
			$this->$key = $value;
		});
	}

	public function toArray() : array
	{
		return get_object_vars($this);
	}
}
