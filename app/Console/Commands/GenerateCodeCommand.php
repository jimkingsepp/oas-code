<?php

namespace App\Console\Commands;

use App\CodeGenerator\SpecReader;
use App\CodeGenerator\OpenApiSpec;
use App\CodeGenerator\ControllerGenerator;
use Illuminate\Console\Command;

// use App\Console\Commands\GenerateCodeCommand;

class GenerateCodeCommand extends Command
{
	const MAKE_CONTROLLERS = 'controller';
	const MAKE_CLASSES = 'classes';
	// const DEFAULT_FILE = 'doordashdrive.json';
	const DEFAULT_FILE = 'petstore.json';

	protected $signature = 'oas-code:generate {file=%s} {--%s} {--%s}';
	protected $description = 'Generates code from OpenAPI spec';
	private $options = [
		self::MAKE_CLASSES => false,
		self::MAKE_CONTROLLERS => false
	];

	public function __construct()
	{
		$this->signature = sprintf($this->signature, self::DEFAULT_FILE, self::MAKE_CLASSES, self::MAKE_CONTROLLERS);
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
			// read in options from command line
			$this->readOptions();
			// read in the OpenAPI spec
			$spec_data = SpecReader::getSpecData($path);
			// create spec object from the spec
			$spec = new OpenApiSpec($spec_data);
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

		} catch (\Exception $e) {
			$this->info($e->getMessage());
		}
	}

	public static function createControllerName($title)
	{
		return \str_replace(' ', '', $title) . 'Controller';
	}

	private function readOptions() : void
	{
		// check the 'controllers' option (create controllers only)
		array_walk($this->options, function ($item, $key) {
			$this->options[$key] = $this->option($key);
		});

		array_walk($this->options, function ($item, $key) {
			if ($this->options[$key]) {
				$this->info("Making $key");
			} else {
				$this->info("NOT making $key");
			}
		});
		exit;
	}
}
