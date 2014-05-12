<?php

namespace Vicious;

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

