<?php

namespace Vicious;

require_once(__DIR__.'/AbstractView.php');
require_once(__DIR__.'/PHTML.php');
require_once(__DIR__.'/Config.php');

class PXML extends PHTML
{
	protected $extension						= 'pxml';
	protected $content_type_header	= 'Content-Type: application/xml; charset=utf-8';
}

}

