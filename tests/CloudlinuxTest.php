<?php

use Detain\Cloudlinux\Cloudlinux;
use PHPUnit\Framework\TestCase;

/**
 * @param string $section
 * @param string $level
 * @param string $text
 * @param string $line
 * @param string $file
 */
function myadmin_log($section, $level, $text, $line = '', $file = '')
{
    $GLOBALS['myadmin_log'] = $section.' '.$level.' '.$text.' '.$line.' '.$file;
}

/**
 * Cloudlinux Test Suite
 */
class CloudlinuxTest extends TestCase
{
    /**
     * @var Cloudlinux
     */
    protected $object;
    protected $generator;

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
        $this->generator = new PHPUnit_Framework_MockObject_Generator();
    }

    /**
     * @param $ipAddress
     * @return bool
     */
    protected function validIp($ipAddress)
    {
        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    public function testTestEnvironment()
    {
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
     * @covers Detain\Cloudlinux\Cloudlinux::log
     */
    public function testLog()
    {
        if (!isset($GLOBALS['myadmin_log'])) {
            $GLOBALS['myadmin_log'] = '';
        }
        $orig = $GLOBALS['myadmin_log'];
        $this->object->log('debug', 'log message');
        $this->assertNotEquals($orig, $GLOBALS['myadmin_log']);
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
        $this->assertArrayHasKey('ip_server_reg', $response['data'], 'IP Server status missing');
    }

    /**
     * @covers Detain\Cloudlinux\Cloudlinux::availability
     */
    public function testAvailability()
    {
        // [ "success" => TRUE, "data" => [ "available" => [ 1, 2, 41, 42, 43, 49, ], "owned" => [], ], ]
        $response = $this->object->availability('127.0.0.1');
        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('success', $response, 'Missing success status in response');
        $this->assertEquals(1, $response['success'], 'The command wasnt successfull and should  have been.');
        $this->assertArrayHasKey('data', $response, 'Missing data in response');
        $this->assertTrue(is_array($response['data']['available']), 'Missing array of available license types');
        $response = $this->object->availability('1.1.1.1.1');
        $this->assertArrayHasKey('success', $response, 'Missing success status in response');
        $this->assertEquals(false, $response['success'], 'This should return success of FALSE due to invalid ip.');
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
        $response = $this->object->check('66.45.228.100.1');
        $this->assertFalse($response);
    }

    /**
     * @covers Detain\Cloudlinux\Cloudlinux::check
     * @param $ipAddress
     */
    public function Check($ipAddress)
    {
        // ["success" => TRUE, "data" => ["available" => [16, 41, 42, 43, 49], "owned" => [1] ] ]
        $response = $this->object->check($ipAddress);
        $this->assertTrue(is_array($response));
        $this->assertEquals(1, $response[0], 'This should return an array with a 1.');
    }

    /**
     * @covers Detain\Cloudlinux\Cloudlinux::xmlIsLicensed
     */
    public function testXml_isLicensedException()
    {
        $object = new Cloudlinux(getenv('CLOUDLINUX_LOGIN'), 'BAD_KEY');
        $this->assertFalse($object->xmlIsLicensed('66.45.228.100'));
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

    /**
     * @param $ipAddress
     * @throws \Detain\Cloudlinux\XmlRpcException
     */
    public function Xml_isLicensed($ipAddress)
    {
        $response = $this->object->xmlIsLicensed($ipAddress);
        $this->assertTrue(is_array($response));
        //$this->assertTrue(is_int($response[0]), 'This should return an array with a 1.');
    }

    /**
     * @covers Detain\Cloudlinux\Cloudlinux::isLicensed
     */
    public function testIs_licensedNoLicense()
    {
        $this->object->apiType = 'xml';
        $response = $this->object->isLicensed('66.45.228.100');
        $this->assertTrue(is_array($response));
        $this->assertEquals(0, count($response), 'This should return an empty array.');
        $this->object->apiType = 'rest';
        $response = $this->object->isLicensed('66.45.228.100');
        $this->assertTrue(is_array($response));
        $this->assertEquals(0, count($response), 'This should return an empty array.');
    }

    /**
     * @covers Detain\Cloudlinux\Cloudlinux::isLicensed
     * @param $ipAddress
     * @throws \Detain\Cloudlinux\XmlRpcException
     */
    public function isLicensed($ipAddress)
    {
        $response = $this->object->isLicensed($ipAddress);
        $this->assertTrue(is_array($response));
        $this->assertTrue(is_int($response[0]), 'This should return an array with a 1.');
        $object = new Cloudlinux(getenv('CLOUDLINUX_LOGIN'), getenv('CLOUDLINUX_KEY'), 'xml');
        $response = $object->isLicensed($ipAddress);
        $this->assertTrue(is_array($response));
        $this->assertTrue(is_int($response[0]), 'This should return an array with a 1.');
    }

    /**
     * @param $response
     * @throws \Detain\Cloudlinux\XmlRpcException
     */
    public function ListRestResponse($response)
    {
        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('success', $response, 'Missing success status in response');
        $this->assertEquals(true, $response['success'], 'The command wasnt successfull and should  have been.');
        $this->assertArrayHasKey('data', $response, 'Missing data in response');
        $entry = $response['data'][0];
        $this->assertTrue(is_array($entry), 'Missing array of license data');
        $this->assertArrayHasKey('created', $entry, 'Missing creation date field');
        $this->assertArrayHasKey('ip', $entry, 'Missing IP field');
        $this->assertArrayHasKey('registered', $entry, 'Missing registered status field');
        $this->assertArrayHasKey('type', $entry, 'Missing type field');
        $this->assertTrue(is_bool($entry['registered']), 'registered should be a boolean');
        $this->assertTrue(is_int($entry['type']), 'Type should be an integer');
        $this->assertTrue($this->validIp($entry['ip']), 'ip should be a valid ip address');
        $this->Check($entry['ip']);
        $this->isLicensed($entry['ip']);
    }

    /**
     * @param $response
     * @throws \Detain\Cloudlinux\XmlRpcException
     */
    public function ListXmlResponse($response)
    {
        $this->assertTrue(is_array($response));
        $entry = $response[0];
        $this->assertTrue(is_array($entry), 'Missing array of license data');
        $this->assertArrayHasKey('IP', $entry, 'Missing IP field');
        $this->assertArrayHasKey('REGISTERED', $entry, 'Missing registered status field');
        $this->assertArrayHasKey('TYPE', $entry, 'Missing type field');
        $this->assertTrue(is_bool($entry['REGISTERED']), 'registered should be a boolean');
        $this->assertTrue(is_int($entry['TYPE']), 'Type should be an integer');
        $this->assertTrue($this->validIp($entry['IP']), 'ip should be a valid ip address');
        //$this->Xml_isLicensed($entry['IP']);
        $this->isLicensed($entry['IP']);
    }

    /**
     * @covers Detain\Cloudlinux\Cloudlinux::restList
     */
    public function testRest_list()
    {
        $response = $this->object->restList();
        $this->ListRestResponse($response);
    }

    /**
     * @covers Detain\Cloudlinux\Cloudlinux::reconcile
     */
    public function testReconcile()
    {
        $response = $this->object->reconcile();
        $this->ListXmlResponse($response);
    }

    /**
     * @covers Detain\Cloudlinux\Cloudlinux::licenseList
     */
    public function testLicense_list()
    {
        $this->object->apiType = 'rest';
        $response = $this->object->licenseList();
        $this->ListRestResponse($response);
        $this->object->apiType = 'xml';
        $response = $this->object->licenseList();
        $this->ListXmlResponse($response);
    }

    /**
     * @covers Detain\Cloudlinux\Cloudlinux::license
     */
    public function testLicense()
    {
        $response = $this->object->license('66.45.228.100', 17);
        $this->assertFalse($response);
    }

    /**
     * @covers Detain\Cloudlinux\Cloudlinux::register
     */
    public function testRegister()
    {
        $response = $this->object->register('66.45.228.100', 17);
        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('success', $response, 'Missing success status in response');
        $this->assertEquals(false, $response['success'], 'This should return FALSE as its an invalid license type.');
    }

    /**
     * @covers Detain\Cloudlinux\Cloudlinux::restRemove
     */
    public function testRest_remove()
    {
        $response = $this->object->restRemove('66.45.228.100');
        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('success', $response, 'Missing success status in response');
        $this->assertEquals(true, $response['success'], 'This shoudl return TRUE.');
        $response = $this->object->restRemove('66.45.228.100', 1);
        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('success', $response, 'Missing success status in response');
        $this->assertEquals(true, $response['success'], 'This shoudl return TRUE.');
    }

    /**
     * @covers Detain\Cloudlinux\Cloudlinux::remove
     */
    public function testRemove()
    {
        $this->object->apiType = 'xml';
        $response = $this->object->remove('66.45.228.100');
        $this->assertFalse($response);
        $this->object->apiType = 'rest';
        $response = $this->object->remove('66.45.228.100');
        $this->assertTrue(is_array($response));
        $this->assertArrayHasKey('success', $response, 'Missing success status in response');
        $this->assertEquals(true, $response['success'], 'This shoudl return TRUE.');
    }

    /**
     * @covers Detain\Cloudlinux\Cloudlinux::removeLicense
     */
    public function testRemove_license()
    {
        $response = $this->object->removeLicense('66.45.228.100');
        $this->assertFalse($response);
        $response = $this->object->removeLicense('66.45.228.100', 3);
        $this->assertFalse($response);
        $response = $this->object->removeLicense('66.45.228.100.1');
        $this->assertFalse($response);
    }
}
