<?php

namespace App\CodeGenerator;

abstract class CodeTemplate
{
	const template_directory       = '/CodeGenerator-templates/';
	const controller_template_name = 'ControllerTemplate.tmpl';
	const method_template_name     = 'MethodTemplate.tmpl';
	const class_template_name      = 'ClassTemplate.tmpl';

	protected function getTemplatePath() : string
	{
		return storage_path() . self::template_directory;
	}
}
