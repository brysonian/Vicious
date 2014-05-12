<?php

/**
 * Return a static instance of View Subclass. This works as a singleton and provides easy access to the output object
 * while not requiring that the class itself be a singleton which is undesirable.
 *
 * All classes that want to act as views for vicious should follow this convention.
 */
function pjson() {
	static $instance;
	if (!$instance) $instance = new Vicious\View\PJSON();
	return $instance;
}

function phtml($template=null, $layout=null) {
	static $instance;
	if (!$instance) $instance = new Vicious\View\PHTML();
	if ($template !== null) $instance->set_template($template);
	if ($layout !== null) $instance->set_layout($layout);
	return $instance;
}

function pxml($template=null, $layout=null) {
	static $instance;
	if (!$instance) $instance = new Vicious\View\PXML();
	if ($template !== null) $instance->set_template($template);
	if ($layout !== null) $instance->set_layout($layout);
	return $instance;
}

