<?php

require_once 'PHPUnit/Framework.php';
require_once '../lib/vicious/Router.php';
require_once '../lib/vicious/Config.php';

class RouterTest extends PHPUnit_Framework_TestCase
{
	
	public function setUp() {
		set('base', false);
		
	}

	public function testGet() {
		$r = new RouterTestWrapper();
		$r->get('/', function() {});
		$this->assertEquals(1, count($r->routes['GET']));
	}

	public function testPut() {
		$r = new RouterTestWrapper();
		$r->put('/', function() {echo 'test put';});
		$this->assertEquals(1, count($r->routes['PUT']));
	}

	public function testPost() {
		$r = new RouterTestWrapper();
		$r->post('/', function() {echo 'test post';});
		$this->assertEquals(1, count($r->routes['POST']));
	}

	public function testDelete() {
		$r = new RouterTestWrapper();
		$r->delete('/', function() {echo 'test delete';});
		$this->assertEquals(1, count($r->routes['DELETE']));
	}

	public function testPatternToRegex() {
		$r = new RouterTestWrapper();
		$r->get('/', function() {});
		$this->assertEquals('|^/?()/?$|', $r->routes['GET'][0]->regex());

		$r->get('/thing/:id', function() {});
		$this->assertEquals('|^/?(thing)/([^\/]+)/?|', $r->routes['GET'][1]->regex());

		$r->get('/thing/:name/:id', function() {});
		$this->assertEquals('|^/?(thing)/([^\/]+)/([^\/]+)/?|', $r->routes['GET'][2]->regex());

		$r->get('/gobble/*', function() {});
		$this->assertEquals('|^/?(gobble)/?(.*)/?|', $r->routes['GET'][3]->regex());
	}

	public function testUserRegex() {
		$r = new RouterTestWrapper();
		$r->get(r('|/|'), function() {});
		$this->assertEquals('|/|', $r->routes['GET'][0]->regex());		
	}

	public function testUsesAltDelimiter() {
		$r = new RouterTestWrapper();
		$r->get('|/|', function() {});
		$reg = $r->routes['GET'][0]->regex();
		$this->assertEquals('`', $reg{0});		
	}

	public function testUrlMatchesRoute() {
		$r = new RouterTestWrapper();
		$r->get('/', function() {});
		$this->assertTrue($r->url_matches_route('/', $r->routes['GET'][0]));
	}

	public function testUrlMatchesRouteUsingBase() {
		$r = new RouterTestWrapper();
		$url = '/hello/world';
		set('base', $url);
		$r->get('/', function() {});
		$this->assertTrue($r->url_matches_route($url, $r->routes['GET'][0]));
	}

	public function testUrlMatchesRouteUsingDeepBase() {
		$r = new RouterTestWrapper();
		$base = '/hello/world';
		$url = $base . '/name/bob';
		set('base', $base);
		$r->get('/name/bob', function() {});
		$this->assertTrue($r->url_matches_route($url, $r->routes['GET'][0]));
	}

	public function testParamsForUrlWithRoute() {
		$r = new RouterTestWrapper();
		$r->get('/person/:name/:id', function() {});

		# fail to match /
		$this->assertFalse($r->params_for_url_with_route('/', $r->routes['GET'][0]));
		
		# match something
		$e = array ('name' => 'bob', 'id' => '55');
		$p = $r->params_for_url_with_route('/person/bob/55', $r->routes['GET'][0]);
		$this->assertEquals($e, $p);
		
		# match a splat
		$r->get('/stuff/*', function() {});
		$e = array ('splat' => 'four-score-and-seven-years');
		$p = $r->params_for_url_with_route('/stuff/four-score-and-seven-years', $r->routes['GET'][1]);
		$this->assertEquals($e, $p);

	}

	public function testMatchRequest() {
		$r = new RouterTestWrapper();
		$r->get('/person/:name/:id', function() {});
		$r->get('/post/:title/:id', function() {});
		$r->get('/stuff/*', function() {});
		$r->get('/', function() {});
		
		$this->assertEquals($r->routes['GET'][0], $r->match_request('GET', '/person/bob/55'));
		$this->assertEquals($r->routes['GET'][1], $r->match_request('GET', '/post/super-stuff/99'));
		$this->assertEquals($r->routes['GET'][2], $r->match_request('GET', '/stuff/four-score-and-seven-years'));
		$this->assertEquals($r->routes['GET'][3], $r->match_request('GET', '/'));
		$this->assertFalse($r->match_request('GET', '/blahblah'));
	}

	public function testRouteForRequest() {
		$r = new RouterTestWrapper();
		$r->get('/person/:name/:id', function() {});
		$r->get('/post/:title/:id', function() {});
		$r->get('/stuff/*', function() {});
		$r->get('/', function() {});
		
		$route = $r->route_for_request('GET', '/person/bob/55');
		$e = array ('name' => 'bob', 'id' => '55');
		$this->assertEquals($r->routes['GET'][0], $route);
		$this->assertEquals($e, $route->params());


		$route = $r->route_for_request('GET', '/stuff/four-score-and-seven-years');
		$e = array ('splat' => 'four-score-and-seven-years');
		$this->assertEquals($r->routes['GET'][2], $route);
		$this->assertEquals($e, $route->params());

		$route = $r->route_for_request('GET', '/post/super-stuff/99');
		$e = array ('title' => 'super-stuff', 'id' => '99');
		$this->assertEquals($r->routes['GET'][1], $route);
		$this->assertEquals($e, $route->params());
	}

	public function testProcessRequestVars() {
		$p = array();
		$p['foo'] = 'bar';
		$p['nested']['param1'] = 'nested-1';
		$p['nested']['param2'] = 'nested-2';

		$_POST = $p;
		$_GET['get_var'] = 'v';
		$p = array_merge($p, $_GET);
		
		$r = new RouterTestWrapper();
		$o = $r->process_request_vars(array());
		$this->assertEquals($p, $o);
	
	}

	public function testThrowsNotFound() {
		$this->setExpectedException('vicious\NotFound');
		$router = new vicious\Router();
		$router->route_for_request('GET', '/');		
	}
}


// ===========================================================
// - WRAPPER CLASS FOR TESTING PROTECTED METHODS
// ===========================================================
class RouterTestWrapper extends vicious\Router
{
	public $routes;

	public function process_request_vars($params) {
		return parent::process_request_vars($params);
	}
	
	public function url_matches_route($url, $route) {
		return parent::url_matches_route($url, $route);
	}
	
	public function params_for_url_with_route($url, $route) {
		return parent::params_for_url_with_route($url, $route);
	}
	
	public function match_request($verb, $url) {
		return parent::match_request($verb, $url);
	}

}





?>
