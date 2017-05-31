<?php
/**
 * Cloudlinux Functionality
 *
 * API Documentation at: .. ill fill this in later from forum posts
 *
 * Last Changed: $LastChangedDate: 2017-05-26 04:36:01 -0400 (Fri, 26 May 2017) $
 * @author detain
 * @version $Revision: 24803 $
 * @copyright 2017
 * @package MyAdmin
 * @category Licenses
 */

namespace Detain\Cloudlinux;

/**
 * Cloudlinux Licensing Class
 *
 * XMLRPC Exception codes:
 * 	1 ­ Internal (unknown) Error
 * 	10 ­ Not authorized
 * 	30 ­ Invalid call arguments
 * 	40 ­ Invalid IP format
 *
 * @link https://cln.cloudlinux.com/clweb/downloads/cloudlinux-xmlrpc-api.pdf XML API Documentation
 * @link https://cln.cloudlinux.com/clweb/downloads/cloudlinux-rest-api.pdf REST API Documentation
 *
 * @access public
 */
class Cloudlinux
{
	private $login = '';
	private $key = '';
	private $authToken;
	public $prefix = 'registration.';
	public $encoding = 'utf-8'; // utf-8 / UTF-8
	public $api_type = 'rest';
	public $sslverify = false;
	public $xml_options = [];
	public $xml_url = 'https://cln.cloudlinux.com/clweb/xmlrpc';
	public $rest_url = 'https://cln.cloudlinux.com/api/';
	public $rest_options = [];
	/**
	 * @var XML_RPC2_Client
	 */
	public $xml_client;
	public $response;

	/**
	 * Cloudlinux::__construct()
	 *
	 * @param string $login API Login Name
	 * @param string $key API Key
	 * @param string $type API type to use, can be 'rest' or 'xml'
	 */
	public function __construct($login, $key, $type = 'rest') {
		$this->login = $login;
		$this->key = $key;
		if (!isset($GLOBALS['HTTP_RAW_POST_DATA']))
			$GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents('php://input');
		//if ($api_type == 'xml') {
			require_once('XML/RPC2/Client.php');
			$this->xml_options['prefix'] = $this->prefix;
			$this->xml_options['encoding'] = $this->encoding;
			$this->xml_options['sslverify'] = $this->sslverify;
			$this->xml_client = XML_RPC2_Client::create($this->xml_url, $this->xml_options);
		//} elseif ($this->api_type == 'rest') {
			//$this->rest_options[CURLOPT_SSL_VERIFYHOST] = $this->sslverify;
		//}
	}

	public function log($level, $text, $line = '', $file = '') {
		if (function_exists('myadmin_log'))
			myadmin_log('cloudlinux', $level, $text, $line, $file);
		else
			error_log($text);
	}

	/**
	 * Return system information about several Cloudlinux services
	 *
	 * @return array array of system information
	 */
	public function status() {
		$this->response = getcurlpage($this->rest_url.'status.json', '', $this->rest_options);
		return json_decode($this->response, true);
	}

	/**
	 * Will return information about what kind of license types are available for registration and what types are already used by current account.
	 * @param string $ip ip address to check
	 * @return array returns an array with  available(int[]) ­ list of types that can be used to register new IP license, and owned(int[]) ­ list of types that already registered(owned) by this account
	 */
	public function availability($ip) {
		$this->response = getcurlpage($this->rest_url.'ipl/availability.json?ip='.$ip.'&token='.$this->authToken(), '', $this->rest_options);
		return json_decode($this->response, true);
	}

	/**
	 * Check if IP license is registered by any customer.
	 *
	 * @param string $ip ip address to check
	 * @return array Will return list of registered license types or empty list if provided IP is not registered yet.
	 */
	public function check($ip) {
		$this->response = getcurlpage($this->rest_url.'ipl/check.json?ip='.$ip.'&token='.$this->authToken(), '', $this->rest_options);
		$response = json_decode($this->response, true);
		if ($response['success'] == 1)
			return $response['data'];
		else
			return false;
	}

	/**
	 * Will register IP based license for authorized user.
	 *
	 * @param string $ip ip address to registger
	 * @param int $type IP license type (1,2 or 16)
	 * @return array On success response returns information about created or already registered license.   ip(string)    type(int) ­ license type (1,2,16)   registered(boolean) ­ true if server was registered in CLN with this license (CLN licenses only).     created(string) ­ license creation time
	 */
	public function register($ip, $type) {
		$this->response = getcurlpage($this->rest_url.'ipl/register.json?ip='.$ip.'&type='.$type.'&token='.$this->authToken(), '', $this->rest_options);
		return json_decode($this->response, true);
	}

	/**
	 * Will remove IP based license from authorized user licenses.
	 *
	 * @param string $ip ip address to remove licenses on
	 * @param int $type optional license type. If empty, will remove licenses with all types
	 * @return bool
	 */
	public function rest_remove($ip, $type = 0) {
		if ($type != 0)
			$this->response = getcurlpage($this->rest_url.'ipl/remove.json?ip='.$ip.'&type='.$type.'&token='.$this->authToken(), '', $this->rest_options);
		else
			$this->response = getcurlpage($this->rest_url.'ipl/remove.json?ip='.$ip.'&token='.$this->authToken(), '', $this->rest_options);
		return json_decode($this->response, true);
	}

	/**
	 * Will remove IP based license from authorized user licenses.
	 *
	 * @param string $ip ip address to remove licenses on
	 * @param int $type optional license type. If empty or 0, will remove licenses with all types
	 * @return bool
	 */
	public function remove($ip, $type = 0) {
		if ($this->api_type == 'xml')
			return $this->remove_license($ip, $type);
		else
			return $this->rest_remove($ip, $type);
	}

	/**
	 * Return all IP licenses owned by authorized user.
	 *
	 * @return array an array of licenses each one containing these fields: ip(string)   ype(int) ­ license type (1,2,16)   registered(boolean) ­ true if server was registered in CLN with this license (CLN licenses only).    created(string) ­ license creation time
	 */
	public function rest_list() {
		$this->response = getcurlpage($this->rest_url.'ipl/list.json?token='.$this->authToken(), '', $this->rest_options);
		return json_decode($this->response, true);
	}

	/**
	 * automatic authToken generator
	 *
	 * @return string the authToken
	 */
	public function authToken() {
		$time = time();
		try {
			return $this->login . '|' . $time . '|' . sha1($this->key . $time);
		} catch (Exception $e) {
			$this->log('error', 'Caught exception code: ' . $e->getCode());
			$this->log('error', 'Caught exception message: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Register new IP license.
	 *
	 * @param string $ip IP Address
	 * @param integer $type license type (1,2 or 16)
	 * @throws XmlRpcException for critical errors
	 * @return integer 0 on success, -1 on error
	 */
	public function license($ip, $type) {
		$type = (int)$type;
		try {
			$this->log('error', 'Calling License(' . $this->authToken() . ',' . $ip . ',' . $type . ')');
			$this->response = $this->xml_client->license($this->authToken(), $ip, $type);
			$this->log('error', 'Response: ' . var_export($this->response, true));
			return $this->response;
		} catch (Exception $e) {
			$this->log('error', 'Caught exception code: ' . $e->getCode());
			$this->log('error', 'Caught exception message: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Remove IP licenses with specified type for customer. Also un­registers from CLN server associated with IP.
	 * or
	 * Remove IP licenses with specified type for customer. Also un­registers from CLN server associated with IP.
	 * @param string         $ip   ip address to remove
	 * @param bool|false|int $type optional parameter to specify the type of license to remove (1,2, or 16)
	 * @return integer 0 on success, -1 on error, Error will be returned also if account have no licenses for provided IP.
	 */
	public function remove_license($ip, $type = false) {
		$this->log('info', "Calling CLoudLinux->xml_client->remove_license({$this->authToken()}, {$ip}, {$type})", __LINE__, __FILE__);
		try {
			if ($type === false)
				return $this->response = $this->xml_client->remove_license($this->authToken(), $ip);
			else
				return $this->response = $this->xml_client->remove_license($this->authToken(), $ip, $type);
		} catch (Exception $e) {
			$this->log('error', 'Caught exception code: ' . $e->getCode());
			$this->log('error', 'Caught exception message: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Check if IP license was registered by any customer. Arguments:
	 *
	 * @param string $ip ip address to remove
	 * @param bool $checkAll True will search for any type of license. False ­ only for types 1 or 2
	 * @throws XmlRpcException for critical errors
	 * @return array (list<int>): List of registered license types or empty list if no license found
	 */
	public function is_licensed($ip, $checkAll = true) {
		if ($this->api_type == 'xml')
			return $this->xml_is_licensed($ip, $checkAll);
		else
			return $this->check($ip, $checkAll);
	}
	/**
	 * Check if IP license was registered by any customer. Arguments:
	 *
	 * @param string $ip ip address to remove
	 * @param bool $checkAll True will search for any type of license. False ­ only for types 1 or 2
	 * @throws XmlRpcException for critical errors
	 * @return array (list<int>): List of registered license types or empty list if no license found
	 */
	public function xml_is_licensed($ip, $checkAll = true) {
		try {
			return $this->response = $this->xml_client->is_licensed($this->authToken(), $ip, $checkAll);
		} catch (Exception $e) {
			$this->log('error', 'Caught exception code: ' . $e->getCode());
			$this->log('error', 'Caught exception message: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * @return bool|mixed
	 */
	public function license_list() {
		try {
			return json_decode(getcurlpage('https://cln.cloudlinux.com/api/ipl/list.json?token=' . $this->authToken()));
		} catch (Exception $e) {
			$this->log('error', 'Caught exception code: ' . $e->getCode());
			$this->log('error', 'Caught exception message: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Return list of all IP licenses owned by authorized user
	 *
	 * @throws XmlRpcException for critical errors
	 * @return array (list<structure>): List of structures or empty list. Each structure contains keys:
	 * 	IP(string)
	 * 	TYPE(int) ­ license type
	 * 	REGISTERED(boolean) ­ True if server was registered in CLN with this license
	 */
	public function reconcile() {
		try {
			return $this->response = $this->xml_client->reconcile($this->authToken());
		} catch (Exception $e) {
			$this->log('error', 'Caught exception code: ' . $e->getCode());
			$this->log('error', 'Caught exception message: ' . $e->getMessage());
			return false;
		}
	}

	/*
	public function getKeyInfo($Key) {
	$this->response = $this->xml->__call('partner10.getKeyInfo', array(
	$this->AuthInfo(),
	$Key,
	));
	return $this->response;
	}
	*/
}

