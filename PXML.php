<?php
declare(encoding='UTF-8');

namespace vicious
{

require_once(__DIR__.'/AbstractView.php');
require_once(__DIR__.'/PHTML.php');
require_once(__DIR__.'/Config.php');

class PXML extends PHTML
{
	protected $extension						= 'pxml';
	protected $content_type_header	= 'Content-Type: application/xml; charset=utf-8';
}

}

// ===========================================================
// - TOSS FUNCS IN THE GLOBAL NAMESPACE FOR CONVENIENCE
// ===========================================================
namespace 
{

/**
 * Return a static instance of PXML. This works as a singleton and provides easy access to the output object
 * while not requiring that the class itself be a singleton which is undesirable.
 *
 * All classes that want to act as views for vicious should follow this convention.
 */
function pxml($template=null, $layout=null) {
	static $instance;
	if (!$instance) $instance = new vicious\PXML();	
	if ($template !== null) $instance->set_template($template);
	if ($layout !== null) $instance->set_layout($layout);
	return $instance;
}

}

?>