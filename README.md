# Vicious
Vicious is a [PHP](http://php.net) clone(ish) of [Sinatra](http://sinatrarb.com).

## Getting Started
Vicious attempts to implement most of the Sinatra DSL for quickly creating web applications in PHP with minimal effort:

    # myapp.php
    require('vicious.php');

    get('/', function() {	
    	return 'Hello World!';
    });

To install, download the library and put it in your include path (or just in your web folder).

## Requirements
Vicious takes advantage of a number of PHP 5.3 specific features like namespaces and late static bindings. We're looking into ways to generate a downgraded version for older versions of PHP.


## Htaccess
Vicious, like sinatra, is built around "pretty urls," but to enable these on Apache requires some mod_rewrite voodoo. Vicious comes with a very basic generator that allows you to create an htaccess file ready to use in your app. 

    # cd to your public directory
    cd public
    
    # run the generator
    php vicious.php htaccess routes.php 


The recommend directory layout for an application is to follow a convention similar to that found in sinatra. 
    /views
    /public
        .htaccess
        routes.php
        _css
        _js
        _images
However, Vicious is designed with flexibility in mind, so if you need to put your templates in the public directory you can change the default location using the set() function. To set the views directory to a directory next to the routes.php file you could use:
    
    set ('views', __DIR__.'/views');
    
Note that the htaccess file that ships with Vicious includes a rule to ignore request for anything in a directory staring with an underscore, this helps keep the rewrites to a minimum and provides and easy way to add directories for static content. Of course if you don't like it, just change the .htaccess file.

## Routes
Vicious copies Sinatra's elegant mapping of http method + url pattern with a block. Since PHP lacks the syntactic elegance of Ruby, Vicious takes advantage of the new anonymous function syntax introduced in PHP 5.3. Routes are created by passing a url pattern and function to one of the four HTTP method functions: GET, POST, PUT, DELETE.

    get('/', function() {
        .. show something ..
    });

    post('/', function() {
        .. create something ..
    });

    put('/', function() {
        .. update something ..
    });

    delete('/', function() {
        .. annihilate something ..
    });

Since this syntax is a bit new to PHP, you can also use the classic PHP syntax for a [callback](http://jp.php.net/manual/en/language.pseudo-types.php#language.types.callback), including the syntax for static and object methods. The get route above could be rewritten as:

    function get_root() {
        .. show something ..
    }
    get('/', 'get_root')

Routes are matched in the order they are defined. The first route that matches the request is invoked.

Route patterns may include named parameters, accessible via the params function:

    get('/hello/:name', function() {
        # matches "GET /hello/foo" and "GET /hello/bar"
        # params('name') is 'foo' or 'bar'
        return "Hello ".params('name')."!";
    });

Route patterns may also include a splat (or wildcard) parameter accessible via params('splat'). Note that for now, splats in Vicious are greedy and contain everything to the right of the splat in the URL path. This behavior will be updated soon to be more like the Sinatra implementation.

    get('/things/*', function() {
        # matches "GET /things/foo/bar/baz" and "GET /things/that/i/love"
        # params('splat') is 'foo/bar/baz' or 'that/i/love'
        return 'Splat: '.params('splat');
    });

Route matching with Regular Expressions is possible using the r() function:

    get(r('|/hello/([^/]*)/is/([^/]*)|'), function() {	
        # matches "GET /hello/bob/is/awesome"
        # captures are stored as indexes passed to the params function
    	return 'Indeed, '.params(0).' is '.params(1);
    });

## Views / Templates
Templates are assumed to be located directly under the ./views directory. To use a different views directory:

    set('views', __DIR__ . '/templates');


### PHTML Templates

    # need to require PHTML.php in your app
    require_once('PHTML.php');
    get('/', function() {
    	return phtml('index');
    });

Renders ./views/index.phtml

New template types are under development. [Docs forthcoming...]

### Setting Variables in Templates
Unlike Sinatra, templates are not evaluated in the same context as routes. This is done for a number of reasons, not the least of which are the restrictions inherent in PHP's class syntax. In Vicious, template variables are set on an instance of the renderer. You can instantiate your own, or use the phtml() convenience function to access a static instance of the PHTML render. The phtml() function allows two optional arguments, the first is the template to use and the second is the layout. By convention the template is set in the final line which also returns the instance.

    get('/', function() {
    	phtml()->title = 'Welcome to Vicious';
    	return phtml('index');
    });

If a template named "layout" exists, it will be used each time a template is rendered. You can disable layouts by passing false as the second argument to phtml().

Variables are accessed in templates just as normal local variables:

    <h1><?=$title;?></h1>

## Filters
Filters are evaluated before each request. Unlike Sinatra, filters in Vicious are not executed in the same context, so to set variables that are made available to a handler you must use set() / options(). Any output generated by a filter is sent to the browser after the content-type header and before the results of the renderer.

    before(function () {
	    set('title', 'Welcome');
    });

    get('/', function() {
    	phtml()->title = options('title') . ' to Vicious';
    	return phtml('index');
    });

## Configuration
Run once, at startup, in any environment:

    configure(function () {
    	...
    });

Run only when the environment (options('environment')) is set to 'DEVELOPMENT':

    configure(DEVELOPMENT, function () {
    	...
    });

## Error handling
Though error handlers are executed in a different context than than routes and filters, you still get access to the renderers like phtml(). You can also get values from options(). Error handlers are passed an instance of ViciousException (or a subclass thereof) so you know what went wrong.

### Not Found
When a Vicious\NotFound exception is raised, the not_found handler is invoked:

    not_found(function ($e) {
        return 'This is nowhere to be found';
    });

### Error
The error handler is invoked any time an exception is raised from a route handler or before filter. The exception object is passed to the handler function.

    error(function ($e) {
    	return 'Something went terribly wrong: '. $e->message();
    });

Vicious installs special not_found and error handlers when running under the development environment.









