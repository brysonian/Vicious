<?php
declare(encoding='UTF-8');

namespace vicious
{

require_once(__DIR__.'/AbstractView.php');
require_once(__DIR__.'/Config.php');

class PJSON extends AbstractView
{
	protected $content_type_header	= 'Content-Type: application/json; charset=utf-8';

	public function render() {
		parent::render();
		echo json_encode($this->props);
	}

}

}

// ===========================================================
// - TOSS FUNCS IN THE GLOBAL NAMESPACE FOR CONVENIENCE
// ===========================================================
namespace 
{

/**
 * Return a static instance of PJSON. This works as a singleton and provides easy access to the output object
 * while not requiring that the class itself be a singleton which is undesirable.
 *
 * All classes that want to act as views for vicious should follow this convention.
 */
function pjson() {
	static $instance;
	if (!$instance) $instance = new vicious\PJSON();	
	return $instance;
}

}

?>