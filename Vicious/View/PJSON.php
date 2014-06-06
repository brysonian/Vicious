<?php

namespace Vicious\View;

class PJSON extends AbstractView
{
	protected $content_type_header	= 'Content-Type: application/json; charset=utf-8';

	public function __construct($values=array()) {
		foreach ($values as $key => $value) {
			$this->$key = $value;
		}
	}

	public function render() {
		parent::render();
		echo json_encode($this->props);
	}
}

