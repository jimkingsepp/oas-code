<?php

namespace App\CodeGenerator;

use App\CodeGenerator\CodeTemplate;

class ClassesGenerator extends CodeTemplate
{
	public function __construct($namespace, $schema)
	{
		// open the method template
		$template_content = file_get_contents($this->getTemplatePath() . self::class_template_name);
		// replace elements
		$template_content = str_replace('{{namespace}}', $namespace, $template_content);
		$template_content = str_replace('{{class_name}}', $schema->getName(), $template_content);
		$member_variables = $this->createMemberVariables($schema->getReferences(), $schema->getProperties());
		$template_content = str_replace('{{member_variables}}', $member_variables, $template_content);

		// create class files
		$path = 'app/' . $namespace . '/' . $schema->getName() . '.php';
		$hndl = fopen($path, 'w');
		fwrite($hndl, $template_content);
		fclose($hndl);
	}

	private function createMemberVariables(array $references, Collection $properties) : string
	{
		$member_variables = array_reduce($references, function ($carry, $item) {
			$carry .= "\t/** @var $item object */\n";
			$carry .= "\tprivate $" . strtolower(preg_replace('|(?<=\\w)(?=[A-Z])|', '_$1', $item)) . ";\n";

			return $carry;
		});

		iterator_apply($properties, function ($properties) use (&$member_variables) {
			$current_item = $properties->current();
			$member_variables .= "\t/** " . $current_item->type . " */\n";
			$member_variables .= "\tprivate $" . $current_item->name . ";\n";

			return true;
		}, [$properties]);

		return $member_variables;
	}
}
