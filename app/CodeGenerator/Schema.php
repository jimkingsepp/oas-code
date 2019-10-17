<?php

namespace App\CodeGenerator;

class Schema implements Collectable {
    private $name;
    private $property_collection;

    public function __construct(\stdClass $schema_data, string $name)
    {
        $this->name = $name;
        $this->property_collection = new Collection();
        array_walk($schema_data->properties, function ($value, $key) {
			$this->property_collection->add(new Property($key, (array)$value));
        });
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function toArray() : array
    {
        return [
            'name' => $this->name,
            'properties' => $this->property_collection->toArray()
        ];
    }
}
