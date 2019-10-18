<?php

namespace App\CodeGenerator;

use App\CodeGenerator\Collection;
use App\CodeGenerator\Components;

class OpenApiSpec
{
	private $version_type; // 'swagger' or 'openapi'
	private $version;
	private $info;
	private $servers;       // collection object
	private $paths;         // collection object
	private $components;
	private $tags;

	/** no support for these Open API 3.0 elements
	 *      security
	 *      externalDocs
	 */
	public function __construct(array $spec_data)
	{
		// OpenAPI versioning
		reset($spec_data);
		$this->version_type = key($spec_data);
		if (VersionType::isValidValue($this->version_type) === false) {
			throw new \Exception('OpenAPI specification version is required.');
		}
		if ($this->isOpenAPISpec() === false) {
			throw new \Exception('No support for version 2.0 yet.');
		}
		// info object
		$this->info = new OpenApiObject($spec_data['info']);
		// server collection
		$this->servers = $this->buildServers($spec_data);
		// paths collection
		$this->paths = $this->buildPaths($spec_data['paths']);
		// components
		$this->components = new Components($spec_data['components']);
		// tags object
		if (isset($spec_data['tags'])) {
			$this->tags = new OpenApiObject($spec_data['tags']);
		}
	}

	private function buildServers(array $spec_data) : Collection
	{
		$collection = new Collection;
		if ($this->version_type === VersionType::OpenAPI) {
			array_walk($spec_data['servers'], function ($value, $key) use ($collection) {
				$server = new Server;
				$server->setUrl($value['url']);
				if (isset($value['description'])) {
					$server->setDescription($value['description']);
				}
				if (isset($value['variables'])) {
					$server->setVariables($value['variables']);
				}
				$collection->add($server);
			});
		} else {
			$base_path = $spec_data->basePath ?? '/';
			if (isset($spec_data->host)) {
				$url = $spec_data->host . $base_path;
			} else {
				$url = $base_path;
			}
			$server = new Server;
			$server->setUrl($url);
			$collection->add($server);
		}

		return $collection;
	}

	private function buildPaths(array $path_data) : Collection
	{
		$collection = new Collection;
		// split the data by path
		array_walk($path_data, function ($data, $path) use ($collection) {
			// and then by method -- path item may share the same path but have different method
			array_walk($data, function ($value, $method) use ($path, $collection) {
				$collection->add(new PathItem($path, $method, $value));
			});
		});

		return $collection;
	}

	public function isOpenAPISpec() : string
	{
		return $this->version_type === VersionType::OpenAPI;
	}

	public function getInfo() : object
	{
		return $this->info;
	}

	public function getServers() : Collection
	{
		return $this->servers;
	}

	public function getPaths() : Collection
	{
		return $this->paths;
	}

	public function getComponents() : Components
	{
		return $this->components;
	}
}

abstract class VersionType extends BasicEnum
{
	const __default = self::OpenAPI;

	const OpenAPI = 'openapi';
	const Swagger = 'swagger';
}
