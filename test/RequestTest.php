<?php
require_once '../Request.php';

class RequestTest extends PHPUnit_Framework_TestCase
{

	private $agent = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_2; en-us) AppleWebKit/531.21.8 (KHTML, like Gecko) Version/4.0.4 Safari/531.21.10';
	private $accept = 'application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5';

	public function setUp() {
  	$_SERVER['HTTP_USER_AGENT'] = $this->agent;
  	$_SERVER['HTTP_ACCEPT'] = $this->accept;
		$_SERVER['REQUEST_METHOD'] = 'GET';
	}

	public function testAgent() {
		$r = new vicious\Request('/uri/for/something', 'GET');
		$x = $r->agent;
		$this->assertEquals($x, $this->agent);
	}

	public function testURI() {
		$r = new vicious\Request('/uri/for/something', 'GET');
		$x = $r->uri;
		$this->assertEquals('/uri/for/something', $x);
	}

	public function testAccept() {
		$r = new vicious\Request('/uri/for/something', 'GET');
		$x = $r->accept;
		$this->assertEquals($x[0], 'application/xml');
		$this->assertEquals($x[1], 'application/xhtml+xml');
		$this->assertEquals($x[2], 'text/html');
	}

	public function testMethodOverride() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['_method'] = 'DELETE';
		$r = new vicious\Request();
		$this->assertEquals('DELETE', $r->method);
	}

	public function testMethod() {
		$r = new vicious\Request();
		$this->assertEquals($r->method, 'GET');
	}

	public function testThrowsUnknownProperty() {
		$this->setExpectedException('vicious\UnknownProperty');
		$r = new vicious\Request();
		$x = $r->bad_prop;
	}

	public function testConvenienceFunction() {
		$x = request();
		$this->assertEquals(request(), $x);

		$x = request('method');
		$this->assertEquals('GET', $x);
	}
}
?>
