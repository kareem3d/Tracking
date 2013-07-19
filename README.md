# Tracking
This is a simple class to track the previously visited pages in the session and easily move between them.
This is helpful in situations where you need to redirect the user back to for example the page before login page.

## Usage example

```php
<?php

use Tracking\Tracker;

spl_autoload_register();

session_start();

// Add this line to all requests you want to track.
Tracker::instance()->save();

// In the following examples I will leave all url generating to you, You shouldn't write them manually like
// the way I'm doing here.

// Let's assume that the user has visited the following pages in order
// home -> product -> about-us -> login
// Now assume in the login page he entered the correct username and password, and you
// need to redirect him to the last page he was visiting. That's easy...
$redirectUrl = Tracker::instance()->getBefore('http://www.example.com/login');

// Great this redirectUrl will contain the url to the about us page.

// But what if you don't want him to go to the about us? You can specify except urls array in the
// second parameter.
// This will get the product page url..
$redirectUrl = Tracker::instance()->getBefore('http://www.example.com/login', array('http://www.example.com/about-us'));


// You can also get the visited page by order like so
$url = Tracker::instance()->getByOrder( 4 ); // Home page
$url = Tracker::instance()->getByOrder( 1 ); // Login page


// You can also set another mechanism to save identifier to the current request.
// e.g. in Laravel framework you can use the current route name instead of full urls if you make sure 
// all routes in your application are given names.
Tracker::instance()->setMechanism(function()
{
	// This is in laravel framework but you can return any thing that identify this request
	return Route::currentRouteName();
});

// You can also reverse the save order you made earlier by calling this method
Tracker::instance()->dontSave();

// You can also clear all previously tracked pages
Tracker::instance()->clear();

// At the end of all requests call the done method
Tracker::instance()->done();
```