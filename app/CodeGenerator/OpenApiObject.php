<?php

namespace App\CodeGenerator;

class OpenApiObject
{
	// private $required = [];

	public function __construct(array $data)
	{
		array_walk($data, [$this, 'createMembers']);
	}

	private function createMembers($value, $key)
	{
		static $walking = false;
		if (is_object($value)) {
			$value_array = (array)$value;
			$walking = true;
			array_walk($value_array, [$this, 'createMembers']);
			$walking = false;
			if (count($value_array)) {
				$this->$key = $value_array;
			}
		} else {
			if ($walking === false) {
				$this->$key = $value;
			}
		}
    }

    public function toArray() : array
    {
        return get_object_vars($this);
    }
}
