<?php
declare(encoding='UTF-8');

namespace vicious {

require_once(__DIR__.'/AbstractView.php');
require_once(__DIR__.'/Config.php');

class PHTML extends AbstractView
{
	protected $extension						= 'phtml';
	protected $content_type_header	= 'Content-Type: text/html; charset=utf-8';

	public function render() {
		# make sure we have a template
		if ($this->template === false) throw new TemplateUndefined();
		
		parent::render();
		
		# unpack the props
		extract($this->props);
		
		# trap the buffer
		ob_start();
		
		# include the template
		require \options('views').'/'.$this->template.'.'.$this->extension;
		
		# get the buffer contents
		$parsed = ob_get_contents();
		
		# clean the buffer
		ob_clean();
		
		# if there is a layout
		if ($this->layout) {
			# push the content into the layout
			$content_for_layout = $parsed;
		
			# include the template
			include \options('views').'/'.$this->layout.".".$this->extension;
	
			# get the buffer contents
			$parsed = ob_get_contents();
		}
		
		# close the output buffer
		ob_end_clean();
		
		# echo the result
		echo $parsed;
	}

}

}

// ===========================================================
// - TOSS FUNCS IN THE GLOBAL NAMESPACE FOR CONVENIENCE
// ===========================================================
namespace {

/**
 * Return a static instance of PHTML. This works as a singleton and provides easy access to the output object
 * while not requiring that the class itself be a singleton which is undesirable.
 *
 * All classes that want to act as views for vicious should follow this convention.
 */
function phtml($template=null, $layout=null) {
	static $instance;
	if (!$instance) $instance = new vicious\PHTML();	
	if ($template !== null) $instance->set_template($template);
	if ($layout !== null) $instance->set_layout($layout);
	return $instance;
}

}

?>