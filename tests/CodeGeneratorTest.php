<?php

use App\CodeGenerator\SpecReader;
use App\CodeGenerator\OpenApiSpec;
use App\CodeGenerator\VersionType;
use App\CodeGenerator\Collection;
use App\CodeGenerator\PathItem;
use App\CodeGenerator\ControllerGenerator;
use App\Console\Commands\GenerateCodeCommand;

/* command-line:
vendor/bin/phpunit --colors --testdox
*/

class CodeGeneratorTest extends TestCase
{
	public function testReadSpecFromFile() : array
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
	public function testCreateSpecObjectFromFileData(array $spec_data) : OpenApiSpec
	{
		$spec = new OpenApiSpec($spec_data);
		$this->assertNotNull($spec);
		$this->assertNotNull($spec->getServers());
		$this->assertNotNull($spec->getInfo());

		return $spec;
	}

	/**
	 * @depends testCreateSpecObjectFromFileData
	 */
	public function testCreateEndpointsFromSpecObject(OpenApiSpec $spec) : Collection
	{
		$this->assertNotNull($spec);
		$paths = $spec->getPaths();
		$this->assertNotNull($paths);

		$operationIds = [];
		iterator_apply($paths, function ($paths, &$operationIds) {
			$operationIds[] = $paths->current()->getOperationId();

			return true;
		}, [$paths, &$operationIds]);
		if ($spec->isOpenAPISpec()) {
			$this->assertCount(4, $operationIds, 'Counting operationIds');
			$this->assertSame(
				[
					'FindPets',
					'AddPet',
					'FindPetById',
					'DeletePet'
				],
				$operationIds
			);
		} else {
			$this->assertCount(20, $operationIds, 'Counting operationIds');
		}

		return $paths;
	}

	/**
	 * @depends testCreateEndpointsFromSpecObject
	 */
	public function testCreateRequestBodyForEndpoints(Collection $paths) : void
	{
		$this->markTestSkipped('No test for creating body of endpoint request');
		$request_body_valid = 0;
		$request_body_null = 0;
		iterator_apply($paths, function ($paths, &$request_body_valid, &$request_body_null) {
			$request_body_data = $paths->current()->getRequestBodyData();
			if (!empty($request_body_data)) {
				$request_body_valid++;
				$this->assertNotNull($request_body_data);
			/* optional display of data */
				// echo "\n=== RequestBody for " . $paths->current()->getOperationId() . " ===\n";
				// echo json_encode($request_body_data, JSON_PRETTY_PRINT);
			} else {
				$request_body_null++;
			}

			return true;
		}, [$paths, &$request_body_valid, &$request_body_null]);
	}

	/**
	 * @depends testCreateEndpointsFromSpecObject
	 */
	public function testCreateContentForRequestBodys(Collection $paths) : void
	{
		$this->markTestSkipped('No test for creating content of body of endpoint request');
		$operationIds = [];
		iterator_apply($paths, function ($paths, &$operationIds) {
			$request_content = $paths->current()->getRequestBodyContent();
			if (!empty($request_content)) {
				$this->assertNotNull($request_content);
				/* optional display of data */
				// echo "\n=== RequestBody Content for " . $paths->current()->getOperationId() . " ===\n";
				// echo json_encode($request_content, JSON_PRETTY_PRINT);
			}

			return true;
		}, [$paths, &$operationIds]);
	}

	/**
	 * @depends testCreateSpecObjectFromFileData
	 */
	public function testCreateComponentsFromSpecObject(OpenApiSpec $spec) : ?Collection
	{
		$this->assertNotNull($spec);
		$components = $spec->getComponents();
		$this->assertNotNull($components);

		$component_names = [];
		iterator_apply($components, function ($components, &$component_names) {
			$component_names[] = $components->current()->getName();

			return true;
		}, [$components, &$component_names]);
		if ($spec->isOpenAPISpec()) {
			$count = 19;
			$component_list = [
				'DeliveryItem',
				'Customer',
				'DropoffAddress',
				'DropoffAddressResponse',
				'PickupAddress',
				'CreationPickupAddress',
				'PickupAddressResponse',
				'DeliveryEstimate',
				'Address',
				'DeliveryValidationResponse',
				'ValidDeliveryResponse',
				'DeliveryResponse',
				'DeliveryCancelResponse',
				'ValidationResponse',
				'FieldError',
				'Item',
				'DuplicateDeliveryError',
				'Dasher',
				'Location'
			];
		} else {
			$count = 6;
			$component_list = [
				'Category',
				'Pet',
				'Tag',
				'ApiResponse',
				'Order',
				'User'
			];
		}
		$this->assertCount($count, $component_names, 'Counting component_names');
		$this->assertNotEmpty($component_list);
		$this->assertSame($component_list, $component_names);

		return $components;
	}

	/**
	 * @depends testCreateSpecObjectFromFileData
	 */
	public function testCreateController(OpenApiSpec $spec) : ControllerGenerator
	{
		$controller_name = GenerateCodeCommand::createControllerName($spec->getInfo()->title);
		$controller_name .= 'TEST';
		$generator = new ControllerGenerator($controller_name);
		$this->assertNotNull($generator);

		$this->assertFileExists(ControllerGenerator::createControllerPath($controller_name));

		return $generator;
	}

	/**
	 * @depends testCreateController
	 * @depends testCreateEndpointsFromSpecObject
	 */
	public function testCreateControllerMethods(ControllerGenerator $generator, Collection $paths) : void
	{
		$route_file = base_path() . '/routes/web.php';
		$route_contents = file_get_contents($route_file);
		// create controller & update routes in web.php
		iterator_apply($paths, function ($paths, $generator) {
			$generator->addMethod($paths->current()->getOperationId());

			$generator->updateRoutes(
				$paths->current()->getPath(),
				$paths->current()->getOperation(),
				$paths->current()->getOperationId()
			);

			return true;
		}, [$paths, $generator]);

		// remove test controller file
		$path = ControllerGenerator::createControllerPath($generator->getControllerName());
		$this->assertFileExists($path);
		unlink($path);
		$this->assertFileNotExists($path);

		// restore weh.php routes
		file_put_contents($route_file, $route_contents);
	}

	private function sendEndpoint(PathItem $e) : string
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
			CURLOPT_CUSTOMREQUEST => $e->getOperation(),
			CURLOPT_POSTFIELDS => ''
		]);

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		return $response;
	}
}
