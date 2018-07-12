<?php
	////////////////////////////////////////////////
	// WELCOME TO AETHSUBS V3.0
	////////////////////////////////////////////////

	////////////////////////////////////////////////
	// USER CONFIGURED VALUES

	// Before require_once('your_path_to/.aethsubs.php')
	// you should set your site specific values
	// these should ideally be located in a file level
	// above website document root.

	// The values to set in that file are:

	/*
	define('ROOT', "/home/your-site-root/"); // above public_html
	
	define('ADMIN_EMAIL', 'your email');

	// if you want to report CSPR violations set a target:
	define('CSPR_TARGET', 'https://yoursite.com/cspr.php')
	// Optional
	define('CSPR_EMAIL_SUBJECT', 'Your Site CSPR Violation Report')

	$SHOW_ERRORS_PARAM = 'what';
	$SHOW_ERRORS_VALUE = 'ever';

	$DB_USER = 'test';
	$DB_PASS = 'test';
	$DB_HOST = 'test';

	*/

	// Optionally set a super secure CSP policy header (recommended)
	aeDoCSP();
	////////////////////////////////////////////////

	////////////////////////////////////////////////
	// DEBUG MODE OR NOT?
	if (isset($_GET[$SHOW_ERRORS_PARAM]) and ($_GET[$SHOW_ERRORS_PARAM] == $SHOW_ERRORS_VALUE))
	{
		error_reporting(E_ALL);
		aeIniSet('display_errors', 1);
		define('DEBUG', true);
	}
	else
	{
		error_reporting(0);
		aeIniSet('display_errors', 0);
		define('DEBUG', false);
	}
	////////////////////////////////////////////////

	////////////////////////////////////////////////
	// AethSubs functions
	////////////////////////////////////////////////

	function aeIniSet($key, $val)
	{
		// Attempts to ini_set without causing WARNING if it cannot
		$success = false;
		@ini_set($key, $val);
		if (ini_get($key) == $val) { $success = true; }
		return $success;
	}

	function aeDoCSP()
	{
		// Note by default this allows Google Fonts
		$report = '';
		if (defined(CSPR_TARGET))
		{
			$report = "report-uri '".CSPR_TARGET."'; ";
		}
		header("Content-Security-Policy: default-src 'none'; script-src 'self'; connect-src 'self'; img-src 'self'; style-src 'self' http://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; manifest-src 'self'; ". $report);
	}

	function aeDoCSPR()
	{
		// call this function in your //domain/cspr.php file
		// Specify the email address that receives the reports
		// in your .my.php config file define('ADMIN_EMAIL', 'your email')
		
		// Specify the desired email subject for violation reports.
		if (defined(CSPR_EMAIL_SUBJECT))
		{
			$SUBJECT = CSPR_EMAIL_SUBJECT;
		}
		else
		{
			$SUBJECT = $_SERVER['HTTP_HOST'] . ' CSP violation';
		}

		// Send `204 No Content` status code.
		http_response_code(204);

		// Get the raw POST data.
		$data = file_get_contents('php://input');
		// Only continue if it’s valid JSON that is not just `null`, `0`, `false` or an
		// empty string, i.e. if it could be a CSP violation report.
		if ($data = json_decode($data)) {
			// Prettify the JSON-formatted data.
			$data = json_encode(
				$data,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			);

			ob_start();
			print_r($_SERVER);
			$data .= ob_get_clean();

			// max email size limit...
			$data = substr($data, 0, 4048);
			
			// Mail the CSP violation report.
			mail(EMAIL, $SUBJECT, $data, 'Content-Type: text/plain;charset=utf-8');
		}
	}

	function aeShowStuff()
	{
		if (DEBUG)
		{
			print_r($_SERVER);

			echo "<br />\n";

		    echo "<div class='gitbranch'>Current branch: <span>" . aeGitCurrentBranch() . "</span></div>"; 
		}
	}

	function aeGitCurrentBranch()
	{
	    $stringfromfile = file('.git/HEAD', FILE_USE_INCLUDE_PATH);

	    $firstLine = $stringfromfile[0]; //get the string from the array

	    $explodedstring = explode("/", $firstLine, 3); //seperate out by the "/" in the string

	    return $explodedstring[2]; //get the one that is always the branch name
	}

?>