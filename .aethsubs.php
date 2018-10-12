<?php
	////////////////////////////////////////////////
	// WELCOME TO AETHSUBS V3.0
	////////////////////////////////////////////////

	////////////////////////////////////////////////
	// USER CONFIGURED VALUES

	// Before require_once('your_path_to/vendor/aetherweb/aethsubs/.aethsubs.php')
	// you should define the location of your site specific
	// config file which contains the following values
	// this should be located in a file level above website
	// document root. 

	// The values to set in that file are:

	/*
	define('ROOT', "/home/your-site-root/"); // above public_html
	
	define('ADMIN_EMAIL', 'your email');

	define('SETCSP', true);

	// if you want to report CSPR violations set a target:
	define('CSPR_TARGET', 'https://yoursite.com/cspr.php')
	// Optional
	define('CSPR_EMAIL_SUBJECT', 'Your Site CSPR Violation Report')

	// to shift to debug mode, pass in the URL debug=**** 
	// where **** is your PW choice
	define('DEBUG_PW', 'your choice');

	define( 'DB_HOST',     'my db host');
    define( 'DB_NAME',     'my db name' );
    define( 'DB_USER',     'my db user' );
    define( 'DB_PASSWORD', 'my db pw' );


	*/

	// Optionally set a super secure CSP policy header (recommended)

	////////////////////////////////////////////////



	////////////////////////////////////////////////
	// AethSubs class definition
	////////////////////////////////////////////////

	class ae 
	{
		private $debug, $ASPSERVER;

		function __construct() 
		{
			////////////////////////////////////////////////
			// SET CSP?
			if (defined('SETCSP') and SETCSP === true)
			{
				$this->DoCSP();
			}
			////////////////////////////////////////////////
			// DEBUG MODE OR NOT?
			if (defined('DEBUG_PW') and isset($_GET['debug']) and ($_GET['debug'] == DEBUG_PW))
			{
				error_reporting(E_ALL);
				$this->IniSet('display_errors', 1);
				$this->debug = true;
			}
			else
			{
				error_reporting(0);
				$this->IniSet('display_errors', 0);
				$this->debug = false;
			}
			////////////////////////////////////////////////
	    }

	    function __destruct() 
	    {
	    	//
	    }

		function IniSet($key, $val)
		{
			// Attempts to ini_set without causing WARNING if it cannot
			$success = false;
			@ini_set($key, $val);
			if (ini_get($key) == $val) { $success = true; }
			return $success;
		}

		function DoCSP()
		{
			// Note by default this allows Google Fonts
			$report = '';
			if (defined('CSPR_TARGET'))
			{
				$report = "report-uri '".CSPR_TARGET."'; ";
			}
			header("Content-Security-Policy: default-src 'none'; script-src 'self'; connect-src 'self'; img-src 'self'; style-src 'self' http://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; manifest-src 'self'; ". $report);
		}

		function DoCSPR()
		{
			// call this function in your //domain/cspr.php file
			// Specify the email address that receives the reports
			// in your .my.php config file define('ADMIN_EMAIL', 'your email')
			
			// Specify the desired email subject for violation reports.
			if (defined('CSPR_EMAIL_SUBJECT'))
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
			if (defined('ADMIN_EMAIL') and ($data = json_decode($data))) 
			{
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
				mail(ADMIN_EMAIL, $SUBJECT, $data, 'Content-Type: text/plain;charset=utf-8');
			}
		}

		function ShowStuff($msg = '')
		{
			if ($this->debug)
			{
				if ($msg <> '')
				{
					echo $msg;
					echo "<br />\n";
				}
				print_r($_SERVER);

				echo "<br />\n";

			    echo "<div class='gitbranch'>Current branch: <span>" . $this->GitCurrentBranch() . "</span></div>"; 
			}
		}

		function GitCurrentBranch()
		{
		    $stringfromfile = file('.git/HEAD', FILE_USE_INCLUDE_PATH);

		    $firstLine = $stringfromfile[0]; //get the string from the array

		    $explodedstring = explode("/", $firstLine, 3); //seperate out by the "/" in the string

		    return $explodedstring[2]; //get the one that is always the branch name
		}

		function Adie($msg)
		{
			ShowStuff($msg);
			die();
		}

		function DBConnect()
		{
			if (isset($this->ASPSERVER))
			{
				// Do not re-connect then!
				return true;
			}
			else
			{
			    if (!($this->ASPSERVER = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME))) 
			    {
			        if ($this->debug) 
			        { 
			        	echo "Could not connect to the database at ".DB_HOST."<br>\n";
					}
			    }
			    else
			    {

			    	return true;
			    }
			}

			return false;
		}

		function APsqlError()
		{
			return mysqli_error($this->ASPSERVER);
		}

		function APFetchObject($res)
		{
			return mysqli_fetch_object($res);
		}

		function APFetchAssoc($res)
		{
			return mysqli_fetch_assoc($res);
		}

		function APFetchRow($res)
		{
			return mysqli_fetch_row($res);
		}

		function APFetchResult($res,$row=0,$col=0)
		{ 
		    $numrows = mysqli_num_rows($res); 
		    if ($numrows && $row <= ($numrows-1) && $row >=0)
		    {
		        mysqli_data_seek($res,$row);
		        $resrow = (is_numeric($col)) ? mysqli_fetch_row($res) : mysqli_fetch_assoc($res);
		        if (isset($resrow[$col])){
		            return $resrow[$col];
		        }
		    }
		    return false;
		}

		function APNumRows($res)
		{
		  return mysqli_num_rows($res);
		}		

		function APquery($query, $params = array())
		{
			// Run passed query and params, return handle

			// In case we have not yet
			$this->DBConnect();

			$statement = mysqli_prepare($this->ASPSERVER, $query) or $this->Adie("Query $query failed prep: " . mysqli_error($this->ASPSERVER));

			// Get the string of data types
			$types = $this->Agettypes($params);

			// How many params were we actually expecting?
			$expect = substr_count($query, '?');

			if ($expect <> sizeof($params))
			{
				$this->Adie("Expecting $expect params but received " . sizeof($params) . " for query $query");
			}

			if (strlen($types) > 0)
			{
			    # make the references
			    $bind_arguments = [];

			    // Param one for call is the types of params (there may be none?)
			    $bind_arguments[] = $types;
			    foreach ($params as $pkey => $pvalue)
			    {
			    	// Cannot allow undefined params ie NULL in some
			    	// fields but empty string is OK. Fudge all such things?
			        $bind_arguments[] = &$params[$pkey]; # bind to array ref
			    }

			    # Bind
			    call_user_func_array(array($statement, 'bind_param'), $bind_arguments);
			}
		    
		    // Execute    
		    $statement->execute() or $this->Adie ("Query $query failed: " . mysqli_error($this->ASPSERVER));

		    // Get result(s)
		    $result = $statement->get_result(); # get results

		    // Destroy now un-needed statement?
		    $statement->close();

		    return $result;
		}

		function mysqlDeleteTables($prefix)
		{
			// deletes all tables matching specific prefix
			echo "DANGEROUS REQUEST - DOUBLE CHECK TABLE LIST - THIS IS CURRENTLY DISABLED";
			$q = "SELECT table_name FROM information_schema.tables WHERE table_name LIKE CONCAT(?,'%') AND table_schema=?";
			$res = $this->APquery($q, array($prefix, DB_NAME));
			while ($tname = $this->APFetchRow($res))
			{
				$tname = $tname[0];
				echo $tname . "<br />\n";
				//$this->APquery("DROP TABLE $tname");
			}
		}

		function mysqlDuplicateTables($oldprefix, $newprefix, $exclude = '/donotdup/')
		{
			// Duplicates all tables matching prefix, replacing
			// the prefix with the new prefix in the duplicate
			$q = "SELECT table_name FROM information_schema.tables WHERE table_name LIKE CONCAT(?,'%') AND table_schema=?";
			$res = $this->APquery($q, array($oldprefix, DB_NAME));
			while ($tname = $this->APFetchRow($res))
			{
				$tname = $tname[0];
				if (!preg_match($exclude, $tname))
				{
					echo "duplication of " . $tname;
					$ntname = preg_replace("/$oldprefix/", $newprefix, $tname);
					echo " to $ntname<br>\n";
					$this->mysqlDuplicateTable($tname, $ntname);
					echo "done<br>\n";
				}
				else
				{
					echo "not duping as $exclude matched " . $tname;
				}
			}
		}

		function mysqlDuplicateTable($old, $new)
		{
			$this->APquery("CREATE TABLE $new LIKE $old");
			$this->APquery("INSERT $new SELECT * FROM $old");
		}

		function Agettypes($params = array())
		{
			$types = '';
			if (sizeof($params)>0) {                        
				foreach($params as $param) {        
				    if(is_int($param)) {
				        $types .= 'i';              //integer
				    } elseif (is_float($param)) {
				        $types .= 'd';              //double
				    } elseif (is_string($param)) {
				        $types .= 's';              //string
				    } else {
				        $types .= 'b';              //blob and unknown
				    }
				}
			}
			return $types;
		}

	}



?>