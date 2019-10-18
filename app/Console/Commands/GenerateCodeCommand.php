<?php

namespace App\Console\Commands;

use App\CodeGenerator\SpecReader;
use App\CodeGenerator\OpenApiSpec;
use App\CodeGenerator\ControllerGenerator;
use Illuminate\Console\Command;

// use App\Console\Commands\GenerateCodeCommand;

class GenerateCodeCommand extends Command
{
	// const DEFAULT_FILE = 'doordashdrive.json';
	const DEFAULT_FILE = 'petstore.json';

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
				$this->info("Making classes");
			}
		} catch (\Exception $e) {
			$this->info($e->getMessage());
		}
	}

	private function makeController(OpenAPISpec $spec) : void
	{
		// create controllers from endpoints
		$controller_name = $this->createControllerName($spec->getInfo()->title);
		$this->info("\nCreating '$controller_name'.\n");
		$generator = new ControllerGenerator($controller_name);

		$endpoints = $spec->getEndpoints();
		iterator_apply($endpoints, function ($endpoints, $generator) {
			$this->info("\tCreating '{$endpoints->current()->getOperationId()}' method.");
			$generator->addMethod($endpoints->current()->getOperationId());

			$this->info("\tUpdating routes.\n");
			$generator->updateRoutes(
						$endpoints->current()->getPath(),
						$endpoints->current()->getMethod(),
						$endpoints->current()->getOperationId()
					);

			return true;
		}, [$endpoints, $generator]);
	}

	public static function createControllerName($title)
	{
		return \str_replace(' ', '', $title) . 'Controller';
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
