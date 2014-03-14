<?php
use Codeception\Util\Stub;
use sweelix\curl\Request;

class GoogleMapTest extends \Codeception\TestCase\Test
{
   /**
	* @var \CodeGuy
	*/
	protected $codeGuy;

	protected $googleMapUrl = 'http://maps.googleapis.com/maps/api/geocode/json';

	protected function _before()
	{
	}

	protected function _after()
	{
	}

	// tests
	public function testResponse()
	{
		$this->codeGuy->wantTo('Run a request and get a response');
		$getParameters = array('address' => '1600 Amphitheatre Parkway, Mountain View, CA', 'sensor' => 'false');
		$request = new Request($this->googleMapUrl);
		$request->setUrlParameters($getParameters);
		$response = $request->execute();
		$this->assertInstanceOf('sweelix\curl\Response', $response);
		$this->assertEquals($response->getStatus(), 200);
		$this->assertTrue(is_array($response->getHeaders()));
		$this->assertNull($response->getHeaderField('non-existing-field'));
		$this->assertNotNull($response->getHeaderField('Content-Type'));
		$this->assertNotNull($response->getRawData());
		$this->assertNotNull($response->getData());
	}

	public function testPost() {
		$this->codeGuy->wantTo('Run a post request');
		$getParameters = array('address' => '1600 Amphitheatre Parkway, Mountain View, CA', 'sensor' => 'false');
		$request = new Request($this->googleMapUrl);
		$request->setMethod('POST');
		$request->setBody(array('bad-body' => true));
		$request->setUrlParameters($getParameters);
		$response = $request->execute();
	}

}