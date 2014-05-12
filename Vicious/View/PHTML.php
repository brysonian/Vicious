<?php

namespace Vicious\View;

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
		$f = $this->template_dir . DIRECTORY_SEPARATOR . $this->template.'.'.$this->extension;
		if (!file_exists($f)) {
			throw new TemplateFileNotFound();
		} else {
			require $f;
		}

		# get the buffer contents
		$parsed = ob_get_contents();

		# clean the buffer
		ob_clean();

		# if there is a layout
		if ($this->layout) {
			# push the content into the layout
			$content_for_layout = $parsed;

			# include the template
			include $this->template_dir . DIRECTORY_SEPARATOR . $this->layout.'.'.$this->extension;

			# get the buffer contents
			$parsed = ob_get_contents();
		}

		# close the output buffer
		ob_end_clean();

		# echo the result
		echo $parsed;
	}

}
