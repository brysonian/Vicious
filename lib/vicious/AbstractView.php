<?php
declare(encoding='UTF-8');

namespace vicious {

require_once(__DIR__.'/ViciousException.php');
require_once(__DIR__.'/Renderable.php');
require_once(__DIR__.'/Config.php');

class AbstractView implements Renderable
{
	protected $_layout							= null;
	protected $template 						= false;
	protected $props								= array();
	protected $extension						= false;
	protected $content_type_header	= false;
	
	public function AbstractView() {}
	
	public function render() {}

	public function send_content_type_header() {
		# set the content_type_header if there is one
		if ($this->content_type_header !== false && !\options('cli')) header($this->content_type_header);
	}
	
	/**
	 * Autodetect the layout
	 * Looks for a file named "layout.$this->extension" in the views dir
	 */
	protected function autofind_layout() {
		if ($this->extension != false) {
			$l = \options('views').'/layout.'.$this->extension;
			if (file_exists($l)) {
				$this->set_layout('layout');
				return 'layout';
			}
		}
		return false;
	}
	
	
// ===========================================================
// - MAGICAL ACCESSORS FOR SETTING PROPERTIES IN THE VIEW
// ===========================================================
	public function __set($k, $v) {
		switch ($k) {
			case 'template':
				$this->set_template($v);
				break;
			
			case 'layout':
				$this->set_layout($v);
				break;
			
			default:
				$this->props[$k] = $v;
		}
	}
	
	public function __get($k) {
		switch ($k) {
			case 'template':
				return $this->template();

			case 'layout':
				return $this->layout();
			
			default:
				return isset($this->props[$k]) ? $this->props[$k] : false;
		}
	}
	
// ===========================================================
// - ACCESSORS
// ===========================================================
	public function template() 				{ return $this->template; }
	public function set_template($t)	{ $this->template = $t; }

	public function layout() 				{ return ($this->_layout === null) ? $this->autofind_layout() : $this->_layout; }
	public function set_layout($l)	{ $this->_layout = $l; }
	
	
}

// ===========================================================
// - EXCEPTIONS
// ===========================================================
class TemplateUndefined extends ViciousException {}

}
?>
