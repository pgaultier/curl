<?php
/**
 * Request.php
 *
 * PHP version 5.3+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   1.0.0
 * @link      http://www.sweelix.net
 * @category  curl
 * @package   sweelix.curl
 */

namespace sweelix\curl;

/**
 * This Request is a simple component which allow the developper
 * to perform easy curl call. Usefull to fetch data from webservices
 * The response is always an Response
 *
 * <code>
 * 	// call http://maps.googleapis.com/maps/api/geocode/json?address=1600+Amphitheatre+Parkway,+Mountain+View,+CA&sensor=false
 * 	$url = 'http://maps.googleapis.com/maps/api/geocode/json';
 * 	$getParameters = array('address' => '1600 Amphitheatre Parkway, Mountain View, CA', 'sensor' => 'false');
 * 	$request = new Request($url);
 * 	$request->setUrlParameters($getParameters);
 * 	$response = $request->execute();
 *
 * 	if($response->getStatus() == 200) {
 * 		var_dump($response->getData());
 * 	} else {
 * 		// there was an error
 * 	}
 * </code>
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   1.0.0
 * @link      http://www.sweelix.net
 * @category  curl
 * @package   sweelix.curl
 */
class Request  {
	/**
	 * @var array Allowed authentication types
	 */
	protected static $authenticationTypes = array('BASIC', 'DIGEST', 'GSSNEGOTIATE', 'NTLM', 'ANY', 'ANYSAFE');
	/**
	 * @var string requested url
	 */
	protected $url;
	/**
	 * @var array headers to add to the request
	 */
	protected $headers=array();
	/**
	 * @var mixed body data, can be a string or an array
	 */
	protected $body;
	/**
	 * @var string VERB used to perfor the request
	 */
	protected $method='GET';
	/**
	 * @var array options passed to curl request
	 */
	protected $curlOptions=array();
	/**
	 * @var array query string parameters
	 */
	protected $parameters;

	/**
	 * Construct a new request
	 *
	 * @param string $url target URL
	 *
	 * @since 1.0.0
	 */
	public function __construct($url) {
		$this->url=$url;
	}

	/**
	 * Add a curl option to current request
	 *
	 * @param mixed $curlOption curl option key
	 * @param mixed $value      curl option value
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function setOption($curlOption, $value) {
		$this->curlOptions[$curlOption] = $value;
	}

	/**
	 * Define request headers
	 *
	 * @param array $headers request headers
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function setHeaders($headers=array()) {
		foreach($headers as $headerKey => $headerValue) {
			$this->setHeaderField($headerKey, $headerValue);
		}
	}

	/**
	 * Define request body
	 *
	 * @param mixed $body body data
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function setBody($body=null) {
		$this->body = $body;
	}

	/**
	 * Define get request parameters
	 *
	 * @param array $parameters get parameters
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function setUrlParameters($parameters) {
		$this->parameters = $parameters;
	}

	/**
	 * Define request header field (one at a time)
	 *
	 * @param string $field   header field name
	 * @param string $content header field value
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function setHeaderField($field, $content) {
		$this->headers[] = $field.': '.$content;
	}

	/**
	 * Define http authentication parameters if needed
	 *
	 * @param string $username username for http auth
	 * @param string $password password for http auth
	 * @param string $type     type for http auth
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function setHttpAuthentication($username = '', $password = '', $type = 'any') {
		$type = strtoupper($type);
		if(in_array($type, self::$authenticationTypes) === true) {
			$this->setOption(CURLOPT_HTTPAUTH, constant('CURLAUTH_' . $type));
			$this->setOption(CURLOPT_USERPWD, $username.':'.$password);
		}
	}

	/**
	 * Define request method
	 *
	 * @param string $method HTTP method
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function setMethod($method) {
		if(in_array($method,array('GET','POST','PUT','DELETE','HEAD'))) {
			$this->method = $method;
		}
	}

	/**
	 * Define proxy authentication parameters
	 *
	 * @param string $username username for proxy auth
	 * @param string $password password for proxy auth
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function setProxyAuthentication($username = '', $password = '') {
		$this->setOption(CURLOPT_PROXYUSERPWD, $username.':'.$password);
	}
	/**
	 * Define timeout for current request
	 *
	 * @param integer $timeout
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function setTimeOut($timeout) {
		$this->setOption(CURLOPT_TIMEOUT, $timeout);
	}

	/**
	 * Set ssl parameters
	 * Enter description here ...
	 * @param boolean $verifyPeer do we have to check the peer
	 * @param mixed   $verifyHost false or integer value 1,2
	 * @param string  $pathToCert path to cert file if needed
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function setSsl($verifyPeer=true,$verifyHost=2,$pathToCert=null) {
		if ($verifyPeer === true) {
			$this->setOption(CURLOPT_SSL_VERIFYPEER, true);
			$this->setOption(CURLOPT_SSL_VERIFYHOST, $verifyHost);
			if($pathToCert !== null) {
				$this->setOption(CURLOPT_CAINFO, $pathToCert);
			}
		} else {
			$this->setOption(CURLOPT_SSL_VERIFYPEER, false);
		}
	}

	/**
	 * Prepare full run, usefull to perform everything.
	 * return an array with (httpCode, responseHeaders, responseBody)
	 *
	 * @return array
	 * @since  1.0.0
	 */
	protected function _preRun() {
		$responseHeaders = '';
		$responseBody = '';
		$this->_setMethod();
		$this->_setBody();

		if(is_array($this->parameters) === true) {
			$params = '';
			if(strpos('?', $this->url) === false) {
				$params = '?';
			}
			$params .= http_build_query($this->parameters);
			$this->setOption(CURLOPT_URL, $this->url.$params);
		} else {
			$this->setOption(CURLOPT_URL, $this->url);
		}
		$this->setOption(CURLOPT_HTTPHEADER, $this->headers);
		$this->setOption(CURLOPT_HEADERFUNCTION,
			function($ch, $data) use(&$responseHeaders) {
				$responseHeaders.=$data;
				return strlen($data);
			}
		);
		$this->setOption(CURLOPT_WRITEFUNCTION,
			function($ch, $data) use(&$responseBody) {
				$responseBody.=$data;
				return strlen($data);
			}
		);
		try {
			$ch = curl_init();
			curl_setopt_array($ch, $this->curlOptions);
			curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			$response = array($httpCode, $responseHeaders, $responseBody);
		} catch(\Exception $e) {
			// trace exception
			$response = null;
		}
		return $response;
	}
	/**
	 * Run current request
	 * and return correct response
	 *
	 * @return Response
	 * @since  1.0.0
	 */
	public function execute() {
		$response = $this->_preRun();
		if($response !== null) {
			$response = new Response($response[0], $response[1], $response[2]);
		}
		return $response;
	}

	/**
	 * Define the method for current request
	 *
	 * @return void
	 * @since  1.0.0
	 */
	protected function _setMethod() {
		switch($this->method) {
			case 'GET' :
				$this->setOption(CURLOPT_HTTPGET, true);
				break;
			case 'POST' :
				$this->setOption(CURLOPT_POST, true);
				break;
			case 'HEAD':
			case 'PUT':
			case 'DELETE':
				$this->setOption(CURLOPT_CUSTOMREQUEST, $this->method);
				break;
		}
	}
	/**
	 * Define request body. Body is
	 * allowed only for POST and PUT methods
	 *
	 * @return void
	 * @since  1.0.0
	 */
	private function _setBody() {
		if($this->body !== null) {
			switch($this->method) {
				case 'POST' :
				case 'PUT' :
					$this->setOption(CURLOPT_POSTFIELDS, $this->body);
					break;
				case 'GET' :
				case 'DELETE':
				case 'HEAD':
					break;
			}
		}
	}
}

