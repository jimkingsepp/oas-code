<?php

namespace App\CodeGenerator;

use Symfony\Component\Yaml\Yaml;

class SpecReader
{
	public static function getSpecData($path)
	{
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		if ($extension === 'json') {
			$json = file_get_contents($path);
			$spec_data = json_decode($json, JSON_OBJECT_AS_ARRAY);
		} elseif ($extension === 'yml' || $extension === 'yaml') {
			$spec_data = Yaml::parseFile($path);
		} else {
			throw new \Exception('No OpenAPI specification found.');
		}

		return $spec_data;
	}
}
