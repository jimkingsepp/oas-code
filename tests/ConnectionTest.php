<?php

use Illuminate\Container\Container;
use App\CodeGenerator\SpecReader;
use App\CodeGenerator\OpenApiSpec;
use App\CodeGenerator\Endpoint;
use App\Console\Commands\GenerateCodeCommand;

class ConnectionTest extends TestCase
{
	public function testReadSpecFromFile() : \stdClass
	{
		$path = storage_path(GenerateCodeCommand::DEFAULT_FILE);
		$this->assertFileExists(realpath($path));
		$spec_data = SpecReader::getSpecData($path);
		$this->assertNotNull($spec_data);

		return $spec_data;
	}

	/**
	 * @depends testReadSpecFromFile
	 */
	public function testCreateSpecObjectFromFileData(\stdClass $spec_data) : OpenApiSpec
	{
		$spec = new OpenApiSpec($spec_data);
		$this->assertNotNull($spec);
		$this->assertNotNull($spec->getServers());
		$this->assertNotNull($spec->getInfo());

		return $spec;
	}

	/**
	 * @testdox Connect to POST Endpoints
     *
     * @depends testCreateSpecObjectFromFileData
	 */
	public function testConnectToPOSTEndpoints(OpenApiSpec $spec) : void
	{
		$this->assertNotNull($spec);
		$endpoints = $spec->getEndpoints();
		$this->assertNotNull($endpoints);

		iterator_apply($endpoints, function ($endpoints) {
			if ($endpoints->current()->getMethod() === 'POST') {
				$response = $this->sendEndpoint($endpoints->current());
				$operationId = $endpoints->current()->getOperationId();
				$this->assertEquals("You have successfully connected to the $operationId endpoint", $response);
			}

			return true;
		}, [$endpoints]);
	}

	private function sendEndpoint(Endpoint $e) : string
	{
		// set URL and other appropriate options
		$curl = curl_init();
		$this->assertNotNull($curl);
		curl_setopt_array($curl, [
			CURLOPT_PORT => '81',
			CURLOPT_URL => 'http://localhost:81/' . $e->getPath(),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => $e->getMethod(),
			CURLOPT_POSTFIELDS => ''
		]);

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		return $response;
	}
}
