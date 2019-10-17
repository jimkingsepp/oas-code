<?php

namespace App\CodeGenerator;

class RequestBodyContent
{
	private $content_type;
	private $required = [];
	private $properties = [];

	public function __construct(\stdClass $content_data)
	{
        $this->content_type = array_keys((array)$content_data)[0];
        $schema = $content_data->{$this->content_type}->schema;
		$this->required = $schema->required ?? null;
		array_walk($schema->properties, function ($value, $key) {
			$this->properties[] = new Property($key, (array)$value);
		});
	}

	public function toArray() : array
	{
		return [
			'content-type' => $this->content_type,
			'required' => $this->required,
			'properties' => array_map(function (Property $p) {
				return $p->toArray();
			}, $this->properties)
		];
	}
}
