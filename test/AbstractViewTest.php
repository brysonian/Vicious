<?php

require_once 'PHPUnit/Framework.php';
require_once '../lib/vicious/AbstractView.php';

class AbstractViewTest extends PHPUnit_Framework_TestCase
{
	public function setUp() {
		# create test templates
		$layout = '<!DOCTYPE html><html><head><title><?= $title; ?></title></head><body><?= $content_for_layout; ?></body></html>';
		$this->tmp = '/tmp/vicious_test';
		if (!file_exists($this->tmp)) mkdir($this->tmp);
	
		$f = fopen($this->tmp.'/layout.phtml', 'w');
		fwrite($f, $layout);
		fclose($f);
		set('views', $this->tmp);		

	}

	public function tearDown() {
		unlink ($this->tmp.'/layout.phtml');
		if (file_exists($this->tmp)) rmdir($this->tmp);
	}

// ===========================================================
// - TESTS
// ===========================================================
	public function testAttemptsToAutofindLayout() {
		$c = new vicious\AbstractView();
		$this->assertNotNull($c->layout());
	}

	public function testAutofindsLayout() {
		$c = new AbstractViewTestWrapper();
		$this->assertEquals('layout', $c->layout());
	}

	public function testTemplateMagicalMutator() {
		$c = new vicious\AbstractView();
		$c->template = 'another-template';
		$this->assertEquals($c->template(), 'another-template');
	}

	public function testLayoutMagicalMutator() {
		$c = new vicious\AbstractView();
		$c->layout = 'another-layout';
		$this->assertEquals($c->layout(), 'another-layout');
	}

	public function testTemplateMutator() {
		$c = new vicious\AbstractView();
		$c->set_template('another-template');
		$this->assertEquals($c->template(), 'another-template');
	}

	public function testLayoutMutator() {
		$c = new vicious\AbstractView();
		$c->set_layout('another-layout');
		$this->assertEquals($c->layout(), 'another-layout');
	}

	public function testGenericMutators() {
		$c = new vicious\AbstractView();
		$c->foo = "bar";
		$this->assertEquals("bar", $c->foo);
	}


}

class AbstractViewTestWrapper extends vicious\AbstractView {
	protected $extension = 'phtml';
}

?>
