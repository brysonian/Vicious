<?php
declare(encoding='UTF-8');

namespace vicious
{

interface Renderable
{
	public function render();
	public function send_content_type_header();
}

}
?>