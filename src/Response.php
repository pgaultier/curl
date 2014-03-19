<?php
/**
 * Response.php
 *
 * PHP version 5.3+
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   1.1.0
 * @link      http://www.sweelix.net
 * @category  curl
 * @package   sweelix.curl
 */

namespace sweelix\curl;

/**
 * This Response is a simple component
 * used by Request. This is a simple object which
 * encapsulate the response.
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   1.1.0
 * @link      http://www.sweelix.net
 * @category  curl
 * @package   sweelix.curl
 */
class Response {
	/**
	 * @var integer response status code
	 */
	protected $statusCode;
	/**
	 * @var array raw curl information
	 */
	protected $rawInformation;

	/**
	 * @var array response headers
	 */
	protected $headers;
	/**
	 * @var array response headers (with lower case keys)
	 */
	protected $lowercaseHeaders;
	/**
	 * @var string response body
	 */
	protected $body;
	/**
	 * Create curl response
	 *
	 * @param integer $statusCode http status code
	 * @param array   $headers    response headers
	 * @param string  $body       reponse body
	 *
	 * @return Response
	 * @since  1.0.0
	 */
	public function __construct($statusCode, $headers=null, $body=null, $rawInfo=null) {
		$this->statusCode = $statusCode;
		$this->headers = static::parseHttpHeaders($headers);
		if(is_array($this->headers) === true) {
			$this->lowercaseHeaders = array_change_key_case($this->headers, CASE_LOWER);
		}
		$this->body = $body;
	}
	/**
	 * Return current status code
	 *
	 * @return integer
	 * @since  1.0.0
	 */
	public function getStatus() {
		return $this->statusCode;
	}
	/**
	 * Get response headers
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public function getHeaders() {
		return $this->headers;
	}
	/**
	 * Get response header
	 *
	 * @param string $field header field
	 *
	 * @return string
	 * @since  1.0.0
	 */
	public function getHeaderField($field) {
		$field = strtolower($field);
		if(isset($this->lowercaseHeaders[$field]) === true) {
			return $this->lowercaseHeaders[$field];
		} else {
			return null;
		}
	}
	/**
	 * Get body as raw data
	 *
	 * @return string
	 * @since  1.0.0
	 */
	public function getRawData() {
		return $this->body;
	}
	/**
	 * Get body as decoded data
	 *
	 * @return mixed
	 * @since  1.0.0
	 */
	public function getData() {
		if(strncmp('application/json', $this->getHeaderField('Content-Type'), 16) == 0) {
			return json_decode($this->body, true);
		} else {
			return $this->body;
		}
	}

	/**
	 * Check if current response is a multipart
	 *
	 * @return boolean
	 * @since  XXX
	 */
	public function getIsMultipart() {
		return (($this->getHeaderField('Content-Type') !== null) && (strncasecmp('multipart', $this->getHeaderField('Content-Type'), 9) === 0));
	}

	/**
	 * Retrieve request information. return null if information field does not exists
	 *
	 * @param string $element name of the field to retrieve @see http://www.php.net/manual/fr/function.curl-getinfo.php
	 *
	 * @return mixed
	 * @since  XXX
	 */
	public function getInfo($element) {
		$response = null;
		if((is_array($this->rawInformation) === true) && (isset($this->rawInformation[$element]) === true)) {
			$response = $this->rawInformation[$element];
		}
		return $response;
	}

	/**
	 * Extract all subresponses which are in multipart form
	 *
	 * @return Response[]
	 * @since  XXX
	 */
	public function extractMultipartDataAsResponse() {
		$result = null;
		if($this->getIsMultipart() === true) {
			$matches = array();
			if(preg_match('#multipart/([^;]+); boundary=([a-z0-9]+)#i', $this->getHeaderField('Content-Type'), $matches) > 0) {
				$delimiter = $matches[2];
				$body = explode("\r\n", $this->_body);
				$part = -1;
				$start = false;
				$end = false;
				$data = array();
				$isHeader = true;
				foreach($body as $i => $line) {
					$skip = false;
					if($line === '--'.$delimiter) {
						$part++;
						$start = true;
						$skip = true;
						$isHeader = true;
					} elseif($line === '--'.$delimiter.'--') {
						$end = true;
						$skip = true;
					}
					if(($start === true) && ($end === false) && ($skip === false)) {
						if(($isHeader === true) && ($line == '')) {
							$isHeader = false;
						} else {
							if(($isHeader === true) && (strncmp('Content-Type', $line, 12) === 0)) {
								$data[$part]['headers'][] = $line;
							} else {
								$data[$part]['body'][] = $line;
							}
						}
					} elseif($end === true) {
						break;
					}
				}
				$result = array();
				foreach($data as $i => $rawResponse) {
					$response = new static($this->statusCode, implode("\r\n", $rawResponse['headers']), implode("\r\n", $rawResponse['body']), $this->rawInformation);
					if($response->getIsMultipart() === true) {
						$subResponses = $response->extractMultipartDataAsResponse();
						if(is_array($subResponses) === true) {
							$result = array_merge($result, $subResponses);
						}
					} else {
						$result[] = $response;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * Parse an HTTP Header string into an asssociative array of
	 * response headers.
	 *
	 * @param string $headers raw http headers
	 *
	 * @return array
	 * @since  XXX
	 */
	public static function parseHttpHeaders($headers) {
		$parsedHeaders = array();
		$fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $headers));
		foreach ($fields as $field) {
			if (preg_match('/([^:]+): (.+)/m', $field, $match)) {
				$match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper(\'\0\')', strtolower(trim($match[1])));
				if (isset($parsedHeaders[$match[1]]) === true) {
					if(is_array($parsedHeaders[$match[1]]) === false) {
						$parsedHeaders[$match[1]] = array($parsedHeaders[$match[1]]);
					} else {
						$parsedHeaders[$match[1]][] = trim($match[2]);
					}
				} else {
					$parsedHeaders[$match[1]] = trim($match[2]);
				}
			}
		}
		return $parsedHeaders;
	}

}