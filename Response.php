<?php
/**
 * Response.php
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
 * This Response is a simple component
 * used by Request. This is a simple object which
 * encapsulate the response.
 *
 * @author    Philippe Gaultier <pgaultier@sweelix.net>
 * @copyright 2010-2014 Sweelix
 * @license   http://www.sweelix.net/license license
 * @version   1.0.0
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
	public function __construct($statusCode, $headers=null, $body=null) {
		$this->statusCode = $statusCode;
		$this->headers = $this->_parseHeaders($headers);
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
	 * Parse HTTP header string into an assoc array
	 *
	 * @param string $headers
	 *
	 * @return array
	 * @since  1.0.0
	 */
	protected function _parseHeaders($headers) {
		$retVal = array();
		$fields = array_filter(explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $headers)));
		foreach ($fields as $field) {
        	if(strncasecmp('http', $field, 4) == 0) {
        		$retVal = array();
        	}else if (preg_match('/([^:]+): (.+)/m', $field, $match)) {
                $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                if (isset($retVal[$match[1]])) {
                    $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }
        }
        return $retVal;
    }
}