<?php

namespace App\CodeGenerator;

use App\CodeGenerator\Collection;
use App\CodeGenerator\Schema;

class OpenApiSpec
{
	private $version_type; // 'swagger' or 'openapi'
	private $version;
	private $info;
	private $servers;
	private $tags;
	private $endpoint_collection;
	private $schema_collection;

	public function __construct($spec_data)
	{
		// OpenAPI versioning
		reset($spec_data);
		$this->version_type = key($spec_data);
		if (VersionType::isValidValue($this->version_type) === false) {
			throw new \Exception('OpenAPI specification version is required.');
		}
		// info object
		$this->info = new OpenApiObject((array)$spec_data->info);
		// server -or- hosts object
		if ($this->version_type === VersionType::OpenAPI) {
			$this->servers = isset($spec_data->servers) ? new OpenApiObject((array)$spec_data->servers) : ['/'];
		} else {
            $base_path = $spec_data->basePath ?? '/';
            if(isset($spec_data->host)) {
                $this->servers = [$spec_data->host . $base_path];
            } else {
                $this->servers = [$base_path];
            }
        }
		// tags object
		$this->tags = new OpenApiObject((array)$spec_data->tags);
		// endpoints
		$this->endpoint_collection = $this->buildEndpointCollection((array)$spec_data->paths);
        // components/schemas -or- definitions
        $schema_data = $spec_data->components->schemas ?? ($spec_data->definitions ?? null);
        $this->schema_collection = $schema_data ? $this->buildSchemaCollection((array)$schema_data) : null;
	}

	private function buildEndpointCollection(array $endpoint_data) : Collection
	{
		$collection = new Collection;
		// split the data by path
		array_walk($endpoint_data, function ($path_data, $path) use ($collection) {
			// and then by method -- endpoints may share the same path but have different method
			array_walk($path_data, function ($value, $method) use ($path, $collection) {
				$collection->add(new Endpoint($path, $method, $value));
			});
		});

		return $collection;
	}

	private function buildSchemaCollection(array $schema_data) : Collection
	{
		$collection = new Collection;
		array_walk($schema_data, function ($schema_data, $name) use ($collection) {
			$collection->add(new Schema($schema_data, $name));
		});

		return $collection;
	}

	public function getVersionType() : string
	{
		return $this->version_type;
	}

	public function getInfo() : object
	{
		return $this->info;
	}

	public function getServers() : array
	{
		return ($this->servers !== '/') ? $this->servers->toArray() : ['/'];
	}

	public function getEndpoints() : Collection
	{
		return $this->endpoint_collection;
	}

	public function getSchemas() : ?Collection
	{
		return $this->schema_collection;
	}
}

abstract class VersionType extends BasicEnum
{
	const __default = self::OpenAPI;

	const OpenAPI = 'openapi';
	const Swagger = 'swagger';
}
