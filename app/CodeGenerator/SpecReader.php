<?php

namespace App\CodeGenerator;

class SpecReader
{
	public static function getSpecData($path)
	{
		$extension = pathinfo($path, PATHINFO_EXTENSION);
		if ($extension === 'json') {
			$json = file_get_contents($path);
			$spec_data = json_decode($json);
		} else {    //  TODO: code for yml files
			$spec_data = null;
		}

		return $spec_data;
	}
}
