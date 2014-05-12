<?php

namespace Vicious\View;

interface Renderable
{
	public function render();
	public function send_content_type_header();
}
