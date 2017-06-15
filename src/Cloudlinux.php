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
	public $prefix = 'registration.';
	public $encoding = 'utf-8'; // utf-8 / UTF-8
	public $apiType = 'rest';
	public $sslverify = false;
	public $xmlOptions = [];
	public $xmlUrl = 'https://cln.cloudlinux.com/clweb/xmlrpc';
	public $restUrl = 'https://cln.cloudlinux.com/api/';
	public $restOptions = [];
	/**
	 * @var \XML_RPC2_Client
	 */
	public $xmlClient;
	public $response;

	/**
	 * Cloudlinux::__construct()
	 *
	 * @param string $login API Login Name
	 * @param string $key API Key
	 * @param string $apiType API type to use, can be 'rest' or 'xml'
	 */
	public function __construct($login, $key, $apiType = 'rest') {
		$this->login = $login;
		$this->key = $key;
		$this->apiType = $apiType;
		$limitType = false;
		if (!$limitType || $this->apiType == 'xml') {
			include_once('XML/RPC2/Client.php');
			$this->xmlOptions['prefix'] = $this->prefix;
			$this->xmlOptions['encoding'] = $this->encoding;
			$this->xmlOptions['sslverify'] = $this->sslverify;
			$this->xmlClient = \XML_RPC2_Client::create($this->xmlUrl, $this->xmlOptions);
		}
		if (!$limitType || $this->apiType == 'rest') {
			$this->restOptions[CURLOPT_SSL_VERIFYHOST] = $this->sslverify;
		}
	}

	/**
	 * getcurlpage()
	 * gets a webpage via curl and returns the response.
	 * also it sets a mozilla type agent.
	 * @param string $url        the url of the page you want
	 * @param string $postfields postfields in the format of "v1=10&v2=20&v3=30"
	 * @param string|array $options
	 * @return string the webpage
	 */
	public function getcurlpage($url, $postfields = '', $options = '') {
		$agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2790.0 Safari/537.36';
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_USERAGENT, $agent);
		if (is_array($postfields) || $postfields != '') {
			if (is_array($postfields)) {
				$postdata = [];
				foreach ($postfields as $field => $value) {
					$postdata[] = $field . '=' . urlencode($value);
				}
				curl_setopt($curl, CURLOPT_POSTFIELDS, implode('&', $postdata));
			} else {
				curl_setopt($curl, CURLOPT_POSTFIELDS, $postfields);
			}
		}
		if (is_array($options) && sizeof($options) > 0) {
			foreach ($options as $key => $value) {
				curl_setopt($curl, $key, $value);
			}
		}
		$tmp = curl_exec($curl);
		curl_close($curl);
		$ret = $tmp;
		return $ret;
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
		$this->response = $this->getcurlpage($this->restUrl.'status.json', '', $this->restOptions);
		return json_decode($this->response, true);
	}

	/**
	 * Will return information about what kind of license types are available for registration and what types are already used by current account.
	 * @param string $ipAddress ip address to check
	 * @return array returns an array with  available(int[]) ­ list of types that can be used to register new IP license, and owned(int[]) ­ list of types that already registered(owned) by this account
	 */
	public function availability($ipAddress) {
		$this->response = $this->getcurlpage($this->restUrl.'ipl/availability.json?ip='.$ipAddress.'&token='.$this->authToken(), '', $this->restOptions);
		return json_decode($this->response, true);
	}

	/**
	 * Check if IP license is registered by any customer.
	 *
	 * @param string $ipAddress ip address to check
	 * @return false|array Will return list of registered license types or empty list if provided IP is not registered yet.
	 */
	public function check($ipAddress) {
		$this->response = $this->getcurlpage($this->restUrl.'ipl/check.json?ip='.$ipAddress.'&token='.$this->authToken(), '', $this->restOptions);
		$response = json_decode($this->response, true);
		if ($response['success'] == 1)
			return $response['data'];
		else
			return false;
	}

	/**
	 * Will register IP based license for authorized user.
	 *
	 * @param string $ipAddress ip address to registger
	 * @param int $type IP license type (1,2 or 16)
	 * @return array On success response returns information about created or already registered license.   ip(string)    type(int) ­ license type (1,2,16)   registered(boolean) ­ true if server was registered in CLN with this license (CLN licenses only).     created(string) ­ license creation time
	 */
	public function register($ipAddress, $type) {
		$this->response = $this->getcurlpage($this->restUrl.'ipl/register.json?ip='.$ipAddress.'&type='.$type.'&token='.$this->authToken(), '', $this->restOptions);
		return json_decode($this->response, true);
	}

	/**
	 * Will remove IP based license from authorized user licenses.
	 *
	 * @param string $ipAddress ip address to remove licenses on
	 * @param int $type optional license type. If empty, will remove licenses with all types
	 * @return bool
	 */
	public function restRemove($ipAddress, $type = 0) {
		if ($type != 0)
			$this->response = $this->getcurlpage($this->restUrl.'ipl/remove.json?ip='.$ipAddress.'&type='.$type.'&token='.$this->authToken(), '', $this->restOptions);
		else
			$this->response = $this->getcurlpage($this->restUrl.'ipl/remove.json?ip='.$ipAddress.'&token='.$this->authToken(), '', $this->restOptions);
		return json_decode($this->response, true);
	}

	/**
	 * Will remove IP based license from authorized user licenses.
	 *
	 * @param string $ipAddress ip address to remove licenses on
	 * @param int $type optional license type. If empty or 0, will remove licenses with all types
	 * @return bool|int
	 */
	public function remove($ipAddress, $type = 0) {
		if ($this->apiType == 'xml')
			return $this->removeLicense($ipAddress, $type);
		else
			return $this->restRemove($ipAddress, $type);
	}

	/**
	 * Return all IP licenses owned by authorized user.
	 *
	 * @return array an array of licenses each one containing these fields: ip(string)   ype(int) ­ license type (1,2,16)   registered(boolean) ­ true if server was registered in CLN with this license (CLN licenses only).    created(string) ­ license creation time
	 */
	public function restList() {
		$this->response = $this->getcurlpage($this->restUrl.'ipl/list.json?token='.$this->authToken(), '', $this->restOptions);
		return json_decode($this->response, true);
	}

	/**
	 * automatic authToken generator
	 *
	 * @return false|string the authToken
	 */
	public function authToken() {
		$time = time();
		try {
			return $this->login . '|' . $time . '|' . sha1($this->key . $time);
		} catch (\Exception $e) {
			$this->log('error', 'Caught exception code: ' . $e->getCode());
			$this->log('error', 'Caught exception message: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Register new IP license.
	 *
	 * @param string $ipAddress IP Address
	 * @param integer $type license type (1,2 or 16)
	 * @throws XmlRpcException for critical errors
	 * @return false|integer 0 on success, -1 on error
	 */
	public function license($ipAddress, $type) {
		$type = (int)$type;
		$xmlClient = $this->xmlClient;
		try {
			$this->log('error', 'Calling License(' . $this->authToken() . ',' . $ipAddress . ',' . $type . ')');
			$this->response = $xmlClient->license($this->authToken(), $ipAddress, $type);
			$this->log('error', 'Response: ' . var_export($this->response, true));
			return $this->response;
		} catch (\Exception $e) {
			$this->log('error', 'Caught exception code: ' . $e->getCode());
			$this->log('error', 'Caught exception message: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Remove IP licenses with specified type for customer. Also un­registers from CLN server associated with IP.
	 * or
	 * Remove IP licenses with specified type for customer. Also un­registers from CLN server associated with IP.
	 * @param string         $ipAddress   ip address to remove
	 * @param int $type optional parameter to specify the type of license to remove (1,2, or 16) or 0 for all
	 * @return bool|int 0 on success, -1 on error, Error will be returned also if account have no licenses for provided IP.
	 */
	public function removeLicense($ipAddress, $type = 0) {
		$this->log('info', "Calling CLoudLinux->xmlClient->removeLicense({$this->authToken()}, {$ipAddress}, {$type})", __LINE__, __FILE__);
		try {
			$this->response = $this->remove($ipAddress, $type);
		} catch (\Exception $e) {
			$this->log('error', 'Caught exception code: ' . $e->getCode());
			$this->log('error', 'Caught exception message: ' . $e->getMessage());
			return false;
		}
		return $this->response;
	}

	/**
	 * Check if IP license was registered by any customer. Arguments:
	 *
	 * @param string $ipAddress ip address to remove
	 * @param bool $checkAll True will search for any type of license. False ­ only for types 1 or 2
	 * @throws XmlRpcException for critical errors
	 * @return false|array (list<int>): List of registered license types or empty list if no license found
	 */
	public function isLicensed($ipAddress, $checkAll = true) {
		if ($this->apiType == 'xml')
			return $this->xmlIsLicensed($ipAddress, $checkAll);
		else
			return $this->check($ipAddress);
	}
	/**
	 * Check if IP license was registered by any customer. Arguments:
	 *
	 * @throws XmlRpcException for critical errors
	 * @param string $ipAddress ip address to remove
	 * @param bool $checkAll True will search for any type of license. False ­ only for types 1 or 2
	 * @return false|array (list<int>): List of registered license types or empty list if no license found
	 */
	public function xmlIsLicensed($ipAddress, $checkAll = true) {
		$xmlClient = $this->xmlClient;
		try {
			$this->response = $xmlClient->isLicensed($this->authToken(), $ipAddress, $checkAll);
		} catch (\Exception $e) {
			$this->log('error', 'Caught exception code: ' . $e->getCode());
			$this->log('error', 'Caught exception message: ' . $e->getMessage());
			return false;
		}
		return $this->response;
	}

	/**
	 * @return false|array
	 */
	public function licenseList() {
		try {
			return json_decode($this->getcurlpage($this->restUrl.'ipl/list.json?token=' . $this->authToken()));
		} catch (\Exception $e) {
			$this->log('error', 'Caught exception code: ' . $e->getCode());
			$this->log('error', 'Caught exception message: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Return list of all IP licenses owned by authorized user
	 *
	 * @throws XmlRpcException for critical errors
	 * @return false|array (list<structure>): List of structures or empty list. Each structure contains keys:  IP(string)   TYPE(int) ­ license type  REGISTERED(boolean) ­ True if server was registered in CLN with this license
	 */
	public function reconcile() {
		$xmlClient = $this->xmlClient;
		try {
			$this->response = $xmlClient->reconcile($this->authToken());
		} catch (\Exception $e) {
			$this->log('error', 'Caught exception code: ' . $e->getCode());
			$this->log('error', 'Caught exception message: ' . $e->getMessage());
			return false;
		}
		return $this->response;
	}
}

