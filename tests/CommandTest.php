<?php

// namespace Tests;

use Illuminate\Console\Parser;
use App\Console\Commands\GenerateCodeCommand;

class CommandTest extends TestCase
{
	public function testGenerateCommandHasNoArguments()
	{
		$cmd = 'oas-code:generate';
		$results = Parser::parse($cmd);
		$this->assertEquals($cmd, $results[0]);
		$this->assertEmpty($results[1]); // arguments
	}

	public function testGenerateCommandHasFilenameArgument()
	{
		$cmd = 'oas-code:generate';
		$file = GenerateCodeCommand::DEFAULT_FILE;
		$full_command = $cmd . ' {' . $file . '}';
		$results = Parser::parse($full_command);
		// var_dump($results); exit;
		$this->assertEquals($cmd, $results[0]); // command
		$this->assertNotEmpty($results[1]); // arguments
		$this->assertEquals($results[1][0]->getName(), $file); // arguments

		$result = $this->artisan($cmd . ' bob');
	}

	public function testGenerateCommandHasFullpathArgument()
	{
		$cmd = 'oas-code:generate';
		$fullpath = storage_path(GenerateCodeCommand::DEFAULT_FILE);
		$full_command = $cmd . ' {' . $fullpath . '}';
		$results = Parser::parse($full_command);
		$this->assertEquals($cmd, $results[0]);
	}

	public function testGenerateWithControllerOption()
	{
        $cmd = 'oas-code:generate';
        $option = GenerateCodeCommand::MAKE_CONTROLLERS;
		$results = Parser::parse($cmd . " {--$option}");
        // var_dump($results); exit;
        $this->assertEquals($cmd, $results[0]);
        $this->assertEmpty($results[1]); // arguments
        $this->assertEquals($option, $results[2][0]->getName());
	}
}
