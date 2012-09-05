<?php

require_once '../vicious/Config.php';

class ConfigTest extends PHPUnit_Framework_TestCase
{

	public function testIsSingleton() {
		# new fails, but i can't test this because PHPUnit blows
#		$c = new vicious\Config();
		# instance works
		$c = vicious\Config::instance();
		$this->assertTrue($c instanceof vicious\Config);

		# subsequent instance is same object
		$g = vicious\Config::instance();
		$this->assertEquals($c, $g);
	}

	public function testThrowsAppFileUndefined() {
		$this->setExpectedException('vicious\AppFileUndefined');
		$c = clone vicious\Config::instance();
		echo $c->app_root;
	}

	public function testAssignsAppRootIfFalse() {
		$c = clone vicious\Config::instance();
		$c->app_file = __FILE__;
		$this->assertEquals(realpath(__DIR__.'/..'), $c->app_root);
	}

	public function testAssignsViewsOnAccessIfFalse() {
		$c = clone vicious\Config::instance();
		$c->app_file = __FILE__;
		$this->assertInternalType('string', $c->views);
	}

	public function testGenericMutators() {
		$c = clone vicious\Config::instance();
		$c->foo = "bar";
		$this->assertEquals("bar", $c->foo);
	}

	public function testAssignsReturnsDefinedViews() {
		$c = clone vicious\Config::instance();
		$c->views = '/views';
		$this->assertEquals("/views", $c->views);
	}

	public function testSet() {
		set("key", "value");

		$c = clone vicious\Config::instance();
		$this->assertEquals("value", $c->key);
	}

	public function testOptions() {
		$c = vicious\Config::instance();
		$c->name = 'bob';
		$this->assertEquals("bob", options('name'));
	}

	public function testEnable() {
		enable('on');
		$c = clone vicious\Config::instance();
		$this->assertTrue($c->on);
	}

	public function testDisable() {
		disable('off');
		$c = clone vicious\Config::instance();
		$this->assertFalse($c->off);
	}


}
?>
