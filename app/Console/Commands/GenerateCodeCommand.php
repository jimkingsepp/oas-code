<?php

namespace App\Console\Commands;

use App\CodeGenerator\SpecReader;
use App\CodeGenerator\OpenApiSpec;
use App\CodeGenerator\ControllerGenerator;
use App\CodeGenerator\Collection;
use Illuminate\Console\Command;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class GenerateCodeCommand extends Command
{
	// const DEFAULT_FILE = 'doordashdrive.json';
	// const DEFAULT_FILE = 'petstore.json';
	const DEFAULT_FILE = 'petstore.yml';

	protected $signature = 'oas-code:generate {file=%s}';
	protected $description = 'Generates code from OpenAPI spec';

	public function __construct()
	{
		$this->signature = sprintf($this->signature, self::DEFAULT_FILE);
		CodeGeneratorOptions::addOptionsToSignature($this->signature);
		parent::__construct();
	}

	public function handle()
	{
		try {
			// read in the 'file' argument
			$file = $this->argument('file');
			// incoming file could be a valid full path
			if (file_exists($file)) {
				$path = $file;
			} else {
				$path = storage_path($file);
				if (file_exists($path) === false) {
					throw new \Exception("Cannot open '$file'");
				}
			}
			// read in the OpenAPI spec
			$spec_data = SpecReader::getSpecData($path);
			// create spec object from the spec
			$spec = new OpenApiSpec($spec_data);
			// read in options from command line
			$options = new CodeGeneratorOptions;
			$options->readOptions($this->options());
			if ($options->wantsController()) {
				$this->makeController($spec);
			}
			if ($options->wantsClasses()) {
				$this->makeClasses($spec->getComponents()->getSchemas(), $spec->getInfo()->getValue('title'));
			}
		} catch (\Exception $e) {
			$this->info($e->getMessage());
		}
	}

	private function makeController(OpenAPISpec $spec) : void
	{
		// create controllers from paths
		$controller_name = $this->createControllerName($spec->getInfo()->getValue('title'));
		$this->info("\nCreating '$controller_name'.\n");
		$generator = new ControllerGenerator($controller_name);

		$paths = $spec->getPaths();
		iterator_apply($paths, function ($paths, $generator) {
			$this->info("\tCreating '{$paths->current()->getOperationId()}' method.");
			$generator->addMethod($paths->current()->getOperationId());

			$this->info("\tUpdating routes.\n");
			$generator->updateRoutes(
				$paths->current()->getPath(),
				$paths->current()->getOperation(),
				$paths->current()->getOperationId()
			);

			return true;
		}, [$paths, $generator]);
	}

	private function makeClasses(Collection $schemas, string $title)
	{
		$this->info('Making classes');
		// make directory for these new class files
		$namespace = $this->createNamespace($title);
		if (file_exists($namespace) === false) {
			mkdir($namespace);
		}

		iterator_apply($schemas, function ($schemas) use ($namespace) {
            $current_schema = $schemas->current();
			// create class files
			$path = $namespace . '/' . $current_schema->getName() . '.php';
			if (file_exists($path) === false) {
				fopen($path, 'w');
			}
			echo json_encode($current_schema->toArray(), JSON_PRETTY_PRINT);
			exit;
		}, [$schemas]);
	}

	public static function createControllerName($title)
	{
		return \str_replace(' ', '', ucwords($title)) . 'Controller';
	}

	private function createNamespace($title)
	{
		return 'app/' . str_replace(' ', '', $title);
	}
}

class CodeGeneratorOptions
{
	const MAKE_CONTROLLERS = 'controller';
	const MAKE_CLASSES = 'classes';
	const OPTIONS = [
		self::MAKE_CLASSES => true,
		self::MAKE_CONTROLLERS => true
	];

	private $options;

	public function __construct()
	{
		$this->options = self::OPTIONS;
	}

	public function readOptions(array $options)
	{
		// read in values from the command line
		array_walk($this->options, function ($item, $key) use ($options) {
			$this->options[$key] = $options[$key];
		});

		// if both options are true, then make classes & controller
		//      the user specifically indicated she wanted both options
		// if both options are false (no options), then make classes & controller
		//      this is the default behavior
		// if only one option is true, then make that option
		//      the user specifically wants only one option

		// if all option values are false (i.e., no option is indicated),
		//      the default option is to make all components so
		//      set all the options to true
		if (in_array(true, $this->options, $strict = true) === false) {
			// all option values are false, so set all option values to true
			$this->options = array_map(function () {
				return true;
			}, $this->options);
		}
	}

	public function wantsController() : bool
	{
		return $this->options[self::MAKE_CONTROLLERS];
	}

	public function wantsClasses() : bool
	{
		return $this->options[self::MAKE_CLASSES];
	}

	public static function addOptionsToSignature(string &$signature) : void
	{
		$signature = array_reduce(
			array_keys(self::OPTIONS),
			function ($carry, $item) {
				$carry .= " {--$item}";

				return $carry;
			},
			$signature
		);
	}
}
