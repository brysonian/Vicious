<?php
require_once 'PHPUnit/Framework.php';
require_once '../lib/vicious/Application.php';
require_once('../lib/vicious/Router.php');
require_once('../lib/vicious/PHTML.php');

class ApplicationTest extends PHPUnit_Framework_TestCase
{
	public function setUp() {
		vicious\Application::init();

		# temp view
		$view = '<!DOCTYPE html><html><head><title><?= $title; ?></title></head><body><h1><?= $title; ?></h1></body></html>';
		$this->tmp = '/tmp/vicious_test';
		if (!file_exists($this->tmp)) mkdir($this->tmp);
		$f = fopen($this->tmp.'/view.phtml', 'w');
		fwrite($f, $view);
		fclose($f);
		set('views', $this->tmp);		
	}

	public function tearDown() {
		unlink ($this->tmp.'/view.phtml');
		if (file_exists($this->tmp)) rmdir($this->tmp);
	}


// ===========================================================
// - TESTS
// ===========================================================
	public function testHandleError() {
		$r = new ApplicationTestWrapper();
		$r->not_found(function($e) {echo 'Not found: '.get_class($e); });
		$r->error(function($e) {echo 'Error: '.get_class($e); });
		
		$e = new vicious\NotFound();
		ob_start();
		$r->handle_error($e);
		$o = ob_get_clean();
		$this->assertEquals('Not found: vicious\NotFound', $o);
		
		$v = new vicious\ViciousException();
		ob_start();
		$r->handle_error($v);
		$o = ob_get_clean();
		$this->assertEquals('Error: vicious\ViciousException', $o);			
	}

	public function testGet() {
		$r = new ApplicationTestWrapper();
		$r->get('/', function() {});
		$this->assertEquals(1, count($r->router->routes['GET']));
	}

	public function testPut() {
		$r = new ApplicationTestWrapper();
		$r->put('/', function() {echo 'test put';});
		$this->assertEquals(1, count($r->router->routes['PUT']));
	}

	public function testPost() {
		$r = new ApplicationTestWrapper();
		$r->post('/', function() {echo 'test post';});
		$this->assertEquals(1, count($r->router->routes['POST']));
	}

	public function testDelete() {
		$r = new ApplicationTestWrapper();
		$r->delete('/', function() {echo 'test delete';});
		$this->assertEquals(1, count($r->router->routes['DELETE']));
	}

	public function testBeforeSetter() {
		$before_one = function() {return 1; };
		$before_two = function() {return 2; };
		$before_three = function() {return 3; };

		$r = new ApplicationTestWrapper();
		$r->before($before_one);
		$r->before($before_two);
		$r->before($before_three);
		$this->assertEquals($before_one, $r->before[0]);
		$this->assertEquals($before_two, $r->before[1]);
		$this->assertEquals($before_three, $r->before[2]);

		$this->assertEquals(1, $r->before[0]());
		$this->assertEquals(2, $r->before[1]());
		$this->assertEquals(3, $r->before[2]());

	}

	public function testConfigureSetter() {
		$config_one = function() {return 1; };
		$config_two = function() {return 2; };
		$config_three = function() {return 3; };

		$r = new ApplicationTestWrapper();
		$r->configure(DEVELOPMENT, $config_one);
		$r->configure(DEVELOPMENT, $config_two);
		$r->configure(DEVELOPMENT, $config_three);
		$this->assertEquals($config_one, $r->config_handlers[DEVELOPMENT][0]);
		$this->assertEquals($config_two, $r->config_handlers[DEVELOPMENT][1]);
		$this->assertEquals($config_three, $r->config_handlers[DEVELOPMENT][2]);

		$this->assertEquals(1, $r->config_handlers[DEVELOPMENT][0]());
		$this->assertEquals(2, $r->config_handlers[DEVELOPMENT][1]());
		$this->assertEquals(3, $r->config_handlers[DEVELOPMENT][2]());
	}

	public function testErrorSetter() {
		$e = function() {return 1; };

		$r = new ApplicationTestWrapper();
		$r->error($e);
		$this->assertEquals($e, $r->error_handler);
		$q = $r->error_handler;
		$this->assertEquals(1, $q());
	}

	public function testNotFoundSetter() {
		$e = function() {return 1; };

		$r = new ApplicationTestWrapper();
		$r->not_found($e);
		$this->assertEquals($e, $r->not_found_handler);
		$q = $r->not_found_handler;
		$this->assertEquals(1, $q());
	}

	public function testParams() {
		$r = new ApplicationTestWrapper();
		$r->get('/person/:name/:id', function() { return '/person'; });
		ob_start();			
		$r->dispatch('/person/bob/55', 'GET');
		$o = ob_get_clean();

		$e = array ('name' => 'bob', 'id' => '55');
		$this->assertEquals($e, $r->params());
		$this->assertEquals('bob', $r->params('name'));
		$this->assertEquals(55, $r->params('id'));
	}

	public function testDispatch() {
		$r = new ApplicationTestWrapper();
		$r->get('/person/:name/:id', function() { return '/person'; });
		$r->get('/', function() { phtml()->title="test"; return phtml('view');});

		ob_start();			
		$r->dispatch('/person/bob/55', 'GET');
		$o = ob_get_clean();

		$this->assertEquals('/person', $o);

		ob_start();			
		$r->dispatch('/', 'GET');
		$o = ob_get_clean();

		$this->assertEquals('<!DOCTYPE html><html><head><title>test</title></head><body><h1>test</h1></body></html>', $o);
	}

	public function testDispatchWithRequestObject() {
		$r = new ApplicationTestWrapper();
		$r->get('/person/:name/:id', function() { return '/person'; });
		$r->get('/', function() { phtml()->title="test"; return phtml('view');});

		ob_start();
		$request = new vicious\Request('/person/bob/55', 'GET');
		$r->dispatch($request);
		$o = ob_get_clean();

		$this->assertEquals('/person', $o);

		ob_start();			
		$request = new vicious\Request('/', 'GET');
		$r->dispatch($request);
		$o = ob_get_clean();

		$this->assertEquals('<!DOCTYPE html><html><head><title>test</title></head><body><h1>test</h1></body></html>', $o);
	}

	public function testBeforeFilter() {
		$r = new ApplicationTestWrapper();
		$r->get('/person/:name/:id', function() { return '/person/'.options('test'); });
		$r->before(function() { echo "FILTER"; set('test', 'testing'); });
		ob_start();			
		$r->dispatch('/person/bob/55', 'GET');
		$o = ob_get_clean();
		$this->assertEquals($o, 'FILTER/person/testing');
	}

	public function testConfigure() {
		$r = new ApplicationTestWrapper();
		$r->get('/', function() { return '/'.options('dev').'/'.options('another_dev'); });
		$r->configure(DEVELOPMENT, function() { set('dev', 'testing'); });
		$r->configure(PRODUCTION, function() { set('dev', 'not-testing'); });
		$r->configure(DEVELOPMENT, function() { set('another_dev', 'testing'); });
		ob_start();			
		$r->dispatch('/', 'GET');
		$o = ob_get_clean();
		$this->assertEquals('/testing/testing', $o);
	}

	public function testConvenienceFunction() {
		$r1 = application();
		$this->assertTrue($r1 instanceof vicious\Application);
		$r2 = vicious\Application::instance();
		$this->assertEquals($r1, $r2);
	}

	public function testSetsAppFileLocationOption() {
		$this->assertTrue(is_string(options('app_file')));
	}

	public function testAddsViciousToIncludePath() {
		vicious\Application::init();
		$this->assertContains(realpath('../lib/vicious/'), get_include_path());
	}


}


// ===========================================================
// - WRAPPER CLASSES FOR TESTING PROTECTED METHODS
// ===========================================================
class RouterTestWrapper extends vicious\Router
{
	public $routes;
}

class ApplicationTestWrapper extends vicious\Application
{
	public $router;
	public $route;
	public $before = array();

	public $error_handler = false;
	public $not_found_handler = false;

	public $config_handlers = array();

	function ApplicationTestWrapper() {
		$this->router = new RouterTestWrapper();

	}
	
	# to keep the "already sent" errs from happening, ugh.
	public function status($s) {
	
	}
}

?>
