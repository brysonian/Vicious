<?php
require_once '../Route.php';

class RouteTest extends PHPUnit_Framework_TestCase
{

	public function testExecutesLambdaHandler() {
		$r = new vicious\Route('/', '/', function() {define('LAMBDA_RAN', true); } );
		$r->execute();
		$this->assertTrue(LAMBDA_RAN);
	}

	public function testExecutesStringHandler() {
		function stringHandler() {
			define('STRING_RAN', true);
		}

		$r = new vicious\Route('/', '/', 'stringHandler' );
		$r->execute();
		$this->assertTrue(STRING_RAN);
	}

	public function testExecutesCallbackHandler() {
		$r = new vicious\Route('/', '/', array($this, 'callback_handler') );
		$r->execute();
		$this->assertTrue(CALLBACK_RAN);
	}

	public function callback_handler() {
			define('CALLBACK_RAN', true);
	}

	public function testThrowsHandlerUndefined() {
		$this->setExpectedException('vicious\HandlerUndefined');
		$r = new vicious\Route('/', '/', false);
		$r->execute();
	}

}
?>
