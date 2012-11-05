<?php
require_once '../PHTML.php';
require_once '../Config.php';

class PHTMLTest extends PHPUnit_Framework_TestCase
{

	private $tmp;
	private $layout			= 'layout';
	private $layout_alt = 'layout_alt';
	private $full				= 'full';
	private $for_layout	= 'for_layout';

	public function setUp() {
		# create test templates
		$for_layout = '<h1><?= $title; ?></h1>';
		$layout = '<!DOCTYPE html><html><head><title><?= $title; ?></title></head><body><?= $content_for_layout; ?></body></html>';
		$layout_alt = '<!DOCTYPE html><html><head><title><?= $title; ?></title></head><body><h2>Alt Layout</h2><?= $content_for_layout; ?></body></html>';
		$full = str_replace('<?= $content_for_layout; ?>', $for_layout, $layout);

		$this->tmp = '/tmp/vicious_test';
		if (!file_exists($this->tmp)) mkdir($this->tmp);

		foreach(array('for_layout', 'full', 'layout_alt', 'layout') as $v) {
			$f = fopen($this->tmp.'/'.$this->$v.'.phtml', 'w');
			fwrite($f, $$v);
			fclose($f);
		}

		set('views', $this->tmp);
	}

	public function tearDown() {
		foreach(array('for_layout', 'full', 'layout_alt', 'layout') as $v) {
			unlink ($this->tmp.'/'.$this->$v.'.phtml');
		}

		if (file_exists($this->tmp)) rmdir($this->tmp);
	}

// ===========================================================
// - TESTS
// ===========================================================
	public function testThrowsTemplateUndefined() {
		$this->setExpectedException('vicious\TemplateUndefined');
		$p = new vicious\PHTML();
		$p->render();
	}

	public function testPHTMLConvenienceFunction() {
		$p = phtml();
		$this->assertEquals($p, phtml());
	}

	public function testRenderTemplateWithAutoLayoutUsingPHTMLConvenience() {
		phtml()->title = "Test";
		ob_start();
		phtml('for_layout')->render();
		$out = ob_get_clean();
		$this->assertContains( '<!DOCTYPE html>', $out );
		$this->assertContains( '<h1>Test</h1>', $out );
		$this->assertContains( '<title>Test</title>', $out );
	}

	public function testRenderTemplateWithoutLayoutUsingPHTMLConvenience() {
		phtml()->title = "Test";

		ob_start();
		phtml('full', false)->render();
		$out = ob_get_clean();
		$this->assertContains( '<!DOCTYPE html>', $out );
		$this->assertContains( '<h1>Test</h1>', $out );
		$this->assertContains( '<title>Test</title>', $out );
	}

	public function testRenderTemplateWithLayoutUsingPHTMLConvenience() {
		phtml()->title = "Test";

		ob_start();
		phtml('for_layout', 'layout')->render();
		$out = ob_get_clean();
		$this->assertContains( '<!DOCTYPE html>', $out );
		$this->assertContains( '<h1>Test</h1>', $out );
		$this->assertContains( '<title>Test</title>', $out );
	}





	public function testRenderTemplateWithAutoLayout() {
		$p = new vicious\PHTML();
		$p->title = "Test";
		$p->template = 'for_layout';
		ob_start();

		$p->render();
		$out = ob_get_clean();
		$this->assertContains( '<!DOCTYPE html>', $out );
		$this->assertContains( '<h1>Test</h1>', $out );
		$this->assertContains( '<title>Test</title>', $out );
	}

	public function testRenderTemplateWithoutLayout() {
		$p = new vicious\PHTML();
		$p->title = "Test";
		$p->template = 'full';

		ob_start();
		$p->render();
		$out = ob_get_clean();
		$this->assertContains( '<!DOCTYPE html>', $out );
		$this->assertContains( '<h1>Test</h1>', $out );
		$this->assertContains( '<title>Test</title>', $out );
	}

	public function testRenderTemplateWithLayout() {
		$p = new vicious\PHTML();
		$p->title = "Test";
		$p->template = 'for_layout';
		$p->layout = 'layout';

		ob_start();
		$p->render();
		$out = ob_get_clean();
		$this->assertContains( '<!DOCTYPE html>', $out );
		$this->assertContains( '<h1>Test</h1>', $out );
		$this->assertContains( '<title>Test</title>', $out );
	}

}
?>
