# aethsubs
Aetherweb's commonly used web routines.

Example usage. If website doc root is here:

/home/mysite/www/


Clone this repo to eg.

/home/mysite/vendor/aetherweb/aethsubs/aethsubs.php


Create a site config file here:

/home/mysite/inc/config.php

Containing eg.

	define('ROOT', "/home/your-site-root/"); // above public_html
	
	define('ADMIN_EMAIL', 'your email');

	// if you want to set a strict CSP header
	define('SETCSP', true);

	// if you want to report CSPR violations set a target:
	define('CSPR_TARGET', 'https://yoursite.com/cspr.php')
	// Optional
	define('CSPR_EMAIL_SUBJECT', 'Your Site CSPR Violation Report')

	// to shift to debug mode, pass in the URL debug=**** 
	// where **** is your PW choice
	define('DEBUG_PW', 'your choice');

	// Database credentials
    define( 'DB_HOST',     'my db host' );
    define( 'DB_NAME',     'my db name' );
    define( 'DB_USER',     'my db user' );
    define( 'DB_PASS',     'my db pw' );

    // now include aethsubs

    require_once(ROOT . "vendor/aetherweb/aethsubs/aethsubs.php");

    // and instantiate an instance of ae

    $ae = new ae();


Then include your config file into the top of all site PHP files eg

in /home/mysite/www/index.php put

    <?php
    	require_once("/home/mysite/inc/config.php");

    	...