<?php

namespace Detain\Cloudlinux\Tests;

use Detain\Cloudlinux\Cloudlinux;
use PHPUnit\Framework\TestCase;

/**
 * Cloudlinux Test Suite
 */
class CloudlinuxTest extends TestCase
{
	/**
	 * @var Cloudlinux
	 */
	protected $object;

	protected function valid_ip($ip) {
		if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false)
			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false)
				return false;
		return true;
	}

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp()
	{
		if (file_exists(__DIR__.'/.env')) {
			$dotenv = new Dotenv\Dotenv(__DIR__);
			$dotenv->load();
		}
		$this->object = new Cloudlinux(getenv('CLOUDLINUX_LOGIN'), getenv('CLOUDLINUX_KEY'));
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown()
	{
	}

	public function testTestEnvironment() {
		$CLOUDLINUX_LOGIN = getenv('CLOUDLINUX_LOGIN');
		$this->assertNotEmpty($CLOUDLINUX_LOGIN, 'No environment variables! Copy .env.example -> .env and fill out your account details.');
		$this->assertInstanceOf('\Detain\Cloudlinux\Cloudlinux', $this->object);
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::getcurlpage
	 */
	public function testGetcurlpage()
	{
		$this->assertRegExp('/<html/i', $this->object->getcurlpage('https://cln.cloudlinux.com'));
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::authToken
	 */
	public function testAuthToken()
	{
		$response = $this->object->authToken();
		$this->assertTrue(is_string($response), 'Token should be a string');
		$this->assertTrue(strpos($response, '|') !== false, 'Token should be split by pipes');
		sleep(1);
		$response2 = $this->object->authToken();
		$this->assertNotEquals($response, $response2, 'Tokens should change as time passes on');
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::status
	 */
	public function testStatus()
	{
		//Array( [success] => 1  [data] => Array([db_rhn_online] => 1  [db_clweb_connected] => 1   [db_clweb_online] => 1  [ip_server_reg] => 1  [xmlrpc] => 1  [rhn_overloaded] =>   [db_rhn_connected] => 1  ) )
		// Remove the following lines when you implement this test.
		$response = $this->object->status();
		$this->assertTrue(is_array($response));
		$this->assertArrayHasKey('success', $response, 'Missing success status in response');
		$this->assertEquals(1, $response['success'], 'The command wasnt successfull and should  have been.');
		$this->assertArrayHasKey('data', $response, 'Missing data in response');
		$this->assertEquals($response['data']['ip_server_reg'], 1, 'IP Server is not up');
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::availability
	 */
	public function testAvailability()
	{
		// [ "success" => true, "data" => [ "available" => [ 1, 2, 41, 42, 43, 49, ], "owned" => [], ], ]
		$response = $this->object->availability('127.0.0.1');
		$this->assertTrue(is_array($response));
		$this->assertArrayHasKey('success', $response, 'Missing success status in response');
		$this->assertEquals(1, $response['success'], 'The command wasnt successfull and should  have been.');
		$this->assertArrayHasKey('data', $response, 'Missing data in response');
		$this->assertTrue(is_array($response['data']['available']), 'Missing array of available license types');
		$response = $this->object->availability('1.1.1.1.1');
		$this->assertArrayHasKey('success', $response, 'Missing success status in response');
		$this->assertEquals(false, $response['success'], 'This should return success of false due to invalid ip.');
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::check
	 */
	public function testCheckNoLicense()
	{
		// []
		$response = $this->object->check('66.45.228.100');
		$this->assertTrue(is_array($response));
		$this->assertEquals(0, count($response), 'This should return a blank array.');
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::check
	 */
	public function Check($ip)
	{
		// ["success" => true, "data" => ["available" => [16, 41, 42, 43, 49], "owned" => [1] ] ]
		$response = $this->object->check($ip);
		$this->assertTrue(is_array($response));
		$this->assertEquals(1, $response[0], 'This should return an array with a 1.');
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::xmlIsLicensed
	 */
	public function testXml_isLicensedNoLicense()
	{
		$response = $this->object->xmlIsLicensed('66.45.228.100');
		$this->assertTrue(is_array($response));
		$this->assertEquals(0, count($response), 'This should return a blank array.');
	}

	public function Xml_isLicensed($ip)
	{
		$response = $this->object->xmlIsLicensed($ip);
		$this->assertTrue(is_array($response));
		$this->assertEquals(1, $response[0], 'This should return an array with a 1.');
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::isLicensed
	 */
	public function testIs_licensedNoLicense()
	{
		$response = $this->object->isLicensed('66.45.228.100');
		$this->assertTrue(is_array($response));
		$this->assertEquals(0, count($response), 'This should return an empty array.');
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::isLicensed
	 */
	public function isLicensed($ip)
	{
		$response = $this->object->isLicensed($ip);
		$this->assertTrue(is_array($response));
		$this->assertEquals(1, $response[0], 'This should return an array with a 1.');
	}

	public function ListResponse($response) {
		$this->assertTrue(is_array($response));
		$this->assertArrayHasKey('success', $response, 'Missing success status in response');
		$this->assertEquals(true, $response['success'], 'The command wasnt successfull and should  have been.');
		$this->assertArrayHasKey('data', $response, 'Missing data in response');
		$entry = $response['data'][0];
		$this->assertTrue(is_array($entry), 'Missing array of license data');
		$response = $this->object->availability('1.1.1.1.1');
		$this->assertArrayHasKey('created', $entry, 'Missing creation date field');
		$this->assertArrayHasKey('ip', $entry, 'Missing IP field');
		$this->assertArrayHasKey('registered', $entry, 'Missing registered status field');
		$this->assertArrayHasKey('type', $entry, 'Missing type field');
		$this->assertTrue(is_bool($entry['registered']), 'registered should be a boolean');
		$this->assertTrue(is_int($entry['type']), 'Type should be an integer');
		$this->assertTrue($this->valid_ip($entry['ip']), 'ip should be a valid ip address');
		$this->Check($entry['ip']);
		$this->Xml_isLicensed($entry['ip']);
		$this->isLicensed($entry['ip']);
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::restList
	 */
	public function testRest_list()
	{
		/**
		 * The normal response will look something like:
		 * 	[
		 * 		'success': true,
		 * 		'data': [
		 * 			[
		 * 				'created': '2017-05-05T16:19-0400',
		 * 				'ip': '66.45.240.186',
		 * 				'registered': true,
		 * 				'type': 1
		 * 			], [
		 * 				'created': '2016-10-14T10:42-0400',
		 * 				'ip': '131.153.38.228',
		 * 				'registered': false,
		 * 				'type': 1
		 * 			],
		 */
		$response = $this->object->restList();
		$this->ListResponse($response);
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::licenseList
	 */
	public function testLicense_list()
	{
		$response = $this->object->licenseList();
		$this->ListResponse($response);
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::register
	 * @todo   Implement testRegister().
	 */
	public function testRegister()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::restRemove
	 * @todo   Implement testRest_remove().
	 */
	public function testRest_remove()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::remove
	 * @todo   Implement testRemove().
	 */
	public function testRemove()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::license
	 * @todo   Implement testLicense().
	 */
	public function testLicense()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::removeLicense
	 * @todo   Implement testRemove_license().
	 */
	public function testRemove_license()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::reconcile
	 * @todo   Implement testReconcile().
	 */
	public function testReconcile()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}
}
