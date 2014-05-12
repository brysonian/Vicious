<?php
declare(encoding='UTF-8');

namespace Vicious
{

interface Renderable
{
	public function render();
	public function send_content_type_header();
}

}
?>