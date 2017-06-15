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
	 * @covers Detain\Cloudlinux\Cloudlinux::status
	 */
	public function testStatus()
	{
		//Array( [success] => 1  [data] => Array([db_rhn_online] => 1  [db_clweb_connected] => 1   [db_clweb_online] => 1  [ip_server_reg] => 1  [xmlrpc] => 1  [rhn_overloaded] =>   [db_rhn_connected] => 1  ) )
		// Remove the following lines when you implement this test.
		$status = $this->object->status();
		$this->assertTrue(is_array($status));
		$this->assertArrayHasKey('success', $status, 'Missing success status in response');
		$this->assertArrayHasKey('data', $status, 'Missing data in response');
		$this->assertEquals($status['data']['ip_server_reg'], 1, 'IP Server is not up');
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::availability
	 * @todo   Implement testAvailability().
	 */
	public function testAvailability()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::check
	 * @todo   Implement testCheck().
	 */
	public function testCheck()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
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
	 * @covers Detain\Cloudlinux\Cloudlinux::rest_remove
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
	 * @covers Detain\Cloudlinux\Cloudlinux::rest_list
	 * @todo   Implement testRest_list().
	 */
	public function testRest_list()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::authToken
	 * @todo   Implement testAuthToken().
	 */
	public function testAuthToken()
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
	 * @covers Detain\Cloudlinux\Cloudlinux::remove_license
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
	 * @covers Detain\Cloudlinux\Cloudlinux::is_licensed
	 * @todo   Implement testIs_licensed().
	 */
	public function testIs_licensed()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::xml_is_licensed
	 * @todo   Implement testXml_is_licensed().
	 */
	public function testXml_is_licensed()
	{
		// Remove the following lines when you implement this test.
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}

	/**
	 * @covers Detain\Cloudlinux\Cloudlinux::license_list
	 * @todo   Implement testLicense_list().
	 */
	public function testLicense_list()
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
