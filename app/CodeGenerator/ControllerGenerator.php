<?php

namespace App\CodeGenerator;

class ControllerGenerator
{
	const template_directory = '/CodeGenerator-templates/';
	const controller_template_name = 'ControllerTemplate.tmpl';
	const method_template_name = 'MethodTemplate.tmpl';

	private $controller_name;

	public function __construct(string $controller_name)
	{
		// create the new controller
		$this->controller_name = $controller_name;
		$this->controller_path = $this->createControllerPath($controller_name);
		if (\file_exists($this->controller_path) === false) {
			// get controller template
			$controller_content = file_get_contents($this->getTemplatePath() . self::controller_template_name);
			// insert class name
			$controller_content = str_replace('[class_name]', $controller_name, $controller_content);
			// write to new controller
			file_put_contents($this->controller_path, $controller_content);
		}
	}

	public function addMethod(string $method_name) : void
	{
		// open the controller
		$controller_content = file_get_contents($this->controller_path);
		// get to the end of the class
		$pos = strrpos($controller_content, '}');
		// create new file contents from original contents
		$new_content = substr($controller_content, 0, $pos);
		// open the method template
		$method_content = file_get_contents($this->getTemplatePath() . self::method_template_name);
		// if the method is already in the controller, don't add it again
		if (strpos($new_content, $method_name) === false) {
			// insert the method name
			$method_content = str_replace('[method_name]', $method_name, $method_content);
			// add newline between methods
			if (strpos($new_content, 'function') !== false) {
				$new_content .= "\n";
			}
			// add to new file contents
			$new_content .= $method_content;
			$new_content .= "}\n";
			// write to controller
			file_put_contents($this->controller_path, $new_content);
		}
	}

	public function updateRoutes($path, $verb, $method_name)
	{
		$new_route = "\$router->$verb('$path', '{$this->controller_name}@$method_name');\n";
		$route_file = base_path() . '/routes/web.php';
		$route_contents = file_get_contents($route_file);
		if (strpos($route_contents, $new_route) === false) {
			file_put_contents($route_file, $new_route, FILE_APPEND);
		}
	}

	private function getTemplatePath() : string
	{
		return storage_path() . self::template_directory;
	}

	public static function createControllerPath(string $controller_name) : string
	{
		$controller_directory = base_path() . '/app/Http/Controllers/';

		return $controller_directory . $controller_name . '.php';
    }

    public function getControllerName() : string
    {
        return $this->controller_name;
    }
}
