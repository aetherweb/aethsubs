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
	// Who owns this site (used as official FROM in emails)
	define('ORG_NAME', "Aetherweb Limited");
	define('ORG_EMAIL', "info@aetherweb.co.uk");

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
    define( 'DB_PASS',     'my db pw' );


	*/

	// Optionally set a super secure CSP policy header (recommended)

	////////////////////////////////////////////////


	////////////////////////////////////////////////
	// AethSubs class definition
	////////////////////////////////////////////////

	class ae 
	{
		private $debug, $ASPSERVER, $field_defs;
		public $data, $Aobject, $Avalue;

		function __construct() 
		{
			////////////////////////////////////////////////
			// MISC INSTANTIATIONS
			$this->field_defs = array();
			$this->data = $_POST; if (sizeof($this->data) <= 0) { $this->data = $_GET; }
			////////////////////////////////////////////////
			// SET CSP?
			if (defined('SETCSP') and SETCSP === true)
			{
				$this->DoCSP();
			}
			////////////////////////////////////////////////
			// DEBUG MODE OR NOT?
			if (defined('DEBUG_PW') and isset($this->data['debug']) and ($this->data['debug'] == DEBUG_PW))
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

	   function dirList ($directory, $pattern = '')
	    {
	        // create an array to hold directory list
	        $results = array();

	        // create a handler for the directory
	        $handler = opendir($directory);

	        // keep going until all files in directory have been read
	        while ($file = readdir($handler)) 
	        {
	            // if $file isn't this directory or its parent,
	            // add it to the results array
	            if ($file != '.' && $file != '..')
				{
					if (($pattern == '') or (preg_match($pattern, $file)))
					{
					    $results[] = $file;
					}
				}
	        }

	        // tidy up: close the handler
	        closedir($handler);

	        // done!
	        return $results;
	    }    

		function AmakeSID ($length = 30)
		{
			// Generates a $length character session id using
			// sha256 and current time + randomness
			//global $ae; used to check for pre-exist in db
			// sha256 makes this not worthwhile in universe
			// life timescale
		    $sid = "" . (intval(rand(1,99)) * time()) . "_" . (intval(rand(1,100000000000))) . "" . microtime();
		    $sid = hash('sha256', $sid);
		    return substr($sid,0,$length);
		}

	    function Ascape($val)
	    {
			// In case we have not yet
			$this->DBConnect();
	    	return mysqli_real_escape_string($this->ASPSERVER, $val);
	    }

		function Amailer($to, $replyto, $subject, $organisation, $content, $attachments = array(), $replyname = '')
		{
			// TODO - ATTACHMENTS ARE NOT IMPLEMENTED YET.
			// TODO - SANITISE DATA BEFORE SENDING. ZERO SANITISATION DONE.
			// $organisation is not used now, present for legacy
			// compatibility


			// Reply to.. ////////////////////////////////////////////
			if (preg_match("/^(.+)\s*\<(.+)\>$/", $replyto, $matches))
			{
				$reply     = $matches[2];
				$replyname = $matches[1];
			}
			else
			{
				$reply     = $replyto;
				if ($replyname == '')
				{
					$replyname = $replyto;
				}
			}
			//////////////////////////////////////////////////////////

			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
			if (defined("ORG_NAME") and defined("ORG_EMAIL"))
			{
				$headers .= "From: ".ORG_NAME." <".ORG_EMAIL.">\r\n";
			}

			$headers .= "Reply-To: $replyname <$replyto>\r\n";

			mail($to, $subject, "<html>\n" . $content . "\n</html>", $headers);
		}


		# image resizer to resize an image
		function AresizeImage($maxwidth, $maxheight, $src_filename, $dest_filename, $forceup = 1, $verbose = 0)
		{
			// By default, this will force the image UP to the dimensions specified. If $forceup is zero tho
			// then it will only resize if either dimension is above the limit
			
			if ($verbose) { $verror .= "started<br/>\n"; }
			
			// Get new sizes
			if (!file_exists($src_filename))
			{
				if ($verbose) { $verror.= "file not exists $src_filename<br/>\n"; }
				$error = "File does not exist: $src_filename";
			}
			else
			{
				if ($verbose) { $verror.= "getting size of $src_filename<br/>\n"; }
			
				list($width, $height, $type, $attr) = getimagesize($src_filename);
				$error = '';
				if (($width > 0) and ($height > 0))
				{
					if ($verbose) { $verror.= "got src image with width $width, height $height<br/>\n"; }

					if (($forceup) or ($width > $maxwidth) or ($height > $maxheight))
					{
						$scale     = min(($maxwidth/$width),($maxheight/$height));
						$newwidth  = round($width * $scale,0);
						$newheight = round($height * $scale, 0);
					}
					else
					{
						$scale = 1;
						$newwidth = $width;
						$newheight = $height;
					}
					// Load
					if ($verbose) { $verror.= "about to do imagecreatetruecolor with size: $newwidth, $newheight<br/>\n"; }

					$dest   = imagecreatetruecolor($newwidth, $newheight);
					
					if ($verbose) { $verror.= "created. Now grab src image into gd image for working on...<br/>\n"; }
					
					if ($src = imagecreatefromstring(file_get_contents($src_filename)))
					{
						// Cool
					}
					else   
					{ 
						if ($verbose) { $verror.= "unrecognised file type. Failing with error.<br/>\n"; }

						$error .= "Unrecognised image file type ($type) received.<br />\n"; 
					}

					if (!$error)
					{
						if (!$src)
						{
						  if ($verbose) { $verror.= "failed to read src file $src_filename<br/>\n"; }
						  $error .= "Problem reading source file $src_filename<br />\n";
						}
						else
						{
							if ($verbose) { $verror.= "about to use imagecopyresampled to resize the image.<br/>\n"; }

							// Resize
							if (imagecopyresampled($dest, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height))
							{
							  # successful resize complete
							  	if ($verbose) { $verror.= "imagecopyresampled worked fine<br/>\n"; }

							}
							else
							{
							  # problem resizing!
							  	if ($verbose) { $verror.= "imagecopyresampled failed :(<br/>\n"; }

							  $error .= "ERROR RESIZING IMAGE!<br />\n";
							}
						
							// Output
								if ($verbose) { $verror.= "kicking out the file as a jpeg with 90 quality...<br/>\n"; }

							if (imagejpeg($dest,$dest_filename,90))
							{
							  # fine
							  	if ($verbose) { $verror.= "kicked it out no problem<br/>\n"; }

							}
							else
							{
							  # Failed to write the file!
							  	if ($verbose) { $verror.= "failed to kick it out to $dest_filename<br/>\n"; }

							  $error .= "ERROR WRITING TO $dest_filename<br />\n";
							}
							
							// Destroy source image
								if ($verbose) { $verror.= "destroying the src image object<br/>\n"; }

							imagedestroy($src);
							
								if ($verbose) { $verror.= "destroyed it<br/>\n"; }
						}
					}		
					
					// Destroy the images...
					if ($verbose) { $verror.= "destroying the dest image object<br/>\n"; }

					imagedestroy($dest);
					
					if ($verbose) { $verror.= "destroyed it<br/>\n"; }
				}
				else
				{
					if ($verbose) { $verror.= "error decoding src image $src_filename<br/>\n"; }
				  	$error .= "Error decoding source image.<br />\n";
				}
			}
			
			if ($verbose)
			{
				mail(ADMIN_EMAIL, 'IM process results', $verror . ' ' . $error);
			}
			return $error;
		}

		function Avalidateemail($email)
		{
		   // Create the syntactical validation regular expression
		   $regexp = "|^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,4})$|i";

		   // Validate the syntax
		   return preg_match($regexp, $email);
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
			$this->ShowStuff($msg);
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
			// In case we have not yet
			$this->DBConnect();
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
		        $resrow = (is_numeric($col)) ? $this->APFetchRow($res) : $this->APFetchAssoc($res);
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

		function APInsertId()
		{
			return mysqli_insert_id($this->ASPSERVER);
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

		function APoneobject($query, $params = array())
		{
		  $qresult = $this->APquery($query, $params);
		  return ($this->Aobject = mysqli_fetch_object($qresult));
		}

		function APonevalue($query, $params = array())
		{
			$qresult = $this->APquery($query, $params);

			$fresult = ($row = $this->APFetchRow($qresult));
			if ($fresult)
			{
				$this->Avalue = $row[0];
			}
			else
			{
				unset($this->Avalue);
			}
			return $fresult;
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

		function GetFieldDefinitions ($tablename)
		{
		  // Ensure tablename is A-Za-z0-9-_
		  $tablename = preg_replace("/[^a-zA-Z0-9-_]/", "", $tablename);
		  if (!isset($this->field_defs[$tablename]))
		  {
			  $result = $this->APquery("SHOW FIELDS FROM $tablename");
			  while ($field = $this->APFetchObject($result))
			  {
				$this->field_defs[$tablename][$field->Field] = $field->Type;
			  }
		  }
		  return $this->field_defs[$tablename];
		}	

		function AhtmlDBFormField ($fieldname, $tablename, $default, $width = '', $rows = '', $namesuffix = '')
		{
		  # attempts to automatically determine the correct type of form field by querying the
		  # table for its description of the passed field name. Then creates an html form field
		  # for that and returns it. Optionally pass a width value to use.
		  $field_defs = $this->GetFieldDefinitions($tablename);

		  if (($this->data[$fieldname . $namesuffix] <> '') and ($this->data[$fieldname . $namesuffix] <> $default))
		  {
		    $default = $this->data[$fieldname . $namesuffix];
		  }

		  # Is this field in the list of errors? If so, give it a red border!
		  $class = '';
		  if ($this->data[$fieldname . "ERROR"] > 0)
		  {
		  	$class = 'redborder';
		  }

		  return $this->AinputHTML($fieldname, $default, $field_defs[$fieldname], $width, $rows, $class, $namesuffix);
		}	

		function AinsertRecord ($tablename, $data = null)
		{		  
		  // Cleanup tablename
		  $tablename = preg_replace("/[^a-zA-Z0-9-_]/", "", $tablename);

		  # optionally pass an array of the values
		  # you have in the $data param. If you pass this, the CGI params will be ignored.

		  # 1. Get a list of all fields in this table, along with their info
		  $field_definitions = $this->GetFieldDefinitions($tablename);

		  # 2. Go through the field list from the field definitions. If we
		  # have a param entry for that field def then add the quoted
		  # value to a list and add the field name to another list...
		  # take care as we may have a blank param that should go in as
		  # blank rather than as the default table value.
		  
		  $params = array();
		  if (!isset($data))
		  {
		  	$data = $this->data;
		  }
		  elseif (is_object($data))
		  {
		  	$data = get_object_vars($data);
		  }
		  $params = array_keys($data);

		  $field_names   = array();
		  $quoted_values = array();
		  $values        = array();
		  $questionmarks = array();
		  $fields        = array();
		  
		  foreach (array_keys($field_definitions) as $field)
		  {
		    if (in_array($field, $params))
		    {
		      array_push ($field_names, '`' . $field . '`');
		      $value = '';
		      $value = $data[$field];
			  array_push ($values, $value . "");
			  array_push ($questionmarks, "?");
		    }
		  }

		  # 4. Insert the new record in a way that our last_insert_id is useful
		  $sql = "INSERT INTO $tablename (" . join(",",$field_names)   . ") VALUES (" . join(",",$questionmarks) . ")";
		  $this->APquery($sql, $values);

		  return $this->APInsertId();
		}

		function AupdateRecord ($tablename, $clause, $data = null)
		{
			// THIS FUNCTION MUST NOT BE CALLED WITH A CLAUSE 
			// CONTAINING TAINTED DATA! YOU MUST CAST CLAUSE VARS!
		  
		  // Cleanup tablename
		  $tablename = preg_replace("/[^a-zA-Z0-9-_]/", "", $tablename);

		  # optionally pass an array of the values
		  # you have in the $data param. If you pass this, the CGI params will be ignored.

		  # 1. Get a list of all fields in this table, along with their info
		  $field_definitions = $this->GetFieldDefinitions($tablename);

		  # 2. Go through the field list from the field definitions. If we
		  # have a param entry for that field def then add the quoted
		  # value to a list and add the field name to another list...
		  # take care as we may have a blank param that should go in as
		  # blank rather than as the default table value.
		  
		  $params = array();
		  if (!isset($data))
		  {
		  	$data = $this->data;
		  }
		  $vars = array();
		  if (is_object($data))
		  {
		  	$vars   = get_object_vars($data);
		  	$params = array_keys($vars);
		  }
		  elseif (is_array($data))
		  {
		  	$vars   = $data;
		    $params = array_keys($data);
		  }
		  else
		  {
		  	Adie("\$data is of unexpected type.");
		  }

		  $sqls = array();
		  $vals = array();
		  foreach (array_keys($field_definitions) as $field)
		  {
		    if (in_array($field, $params))
		    {
			  array_push($sqls, '`' . "$field" . '`' . "=?");
			  array_push($vals, $vars[$field]);
		    }
		  }

		  # 4. Insert the new record in a way that our last_insert_id is useful
		  $sql = "UPDATE $tablename SET " . join(",",$sqls)   . " WHERE $clause";
		  
		  $this->APquery($sql,$vals);
		  return 1;
		}

		function AhtmlTableRecordSelect($table, $show_field, $value_field, $form_field_name, $selected_value = '', $filter = '', $show_field_sql = '', $showblank = 1, $onChange='', $sort = '')
		{
			// THIS HAS NOT BEEN SANITISED! TODO!

			# default to passed value, override for form data if present
			if ($this->data[$form_field_name] <> '') { $selected_value = $this->data[$form_field_name]; }
			
			# been passed a filter?
			if ($filter <> '')
			{
			  $filter = " WHERE $filter ";
			}
			
			# Been passed some show field sql?
			if ($show_field_sql == '')
			{
			  $show_field_sql = $show_field;
			}
			
			$order_by = " ORDER BY $show_field ASC ";
			if ($sort <> '')
			{
				$order_by = " ORDER BY $sort ";
			}
			
			# create a dropdown selection box based on all values in the specified table...
			$result = $this->APquery("SELECT $value_field,$show_field_sql as $show_field FROM $table $filter $order_by");
			$html  = "<select name='$form_field_name' onchange='$onChange'>\n";
			$selected = ''; if ($selected_value == '') { $selected = 'selected="selected"'; }
			if ($showblank)
			{
				$html .= "<option value='' $selected></option>\n";
			}
			while ($item = $this->APFetchObject($result))
			{
			  # this one selected?
			  $selected = '';
			  if ($selected_value == $item->$value_field) { $selected = 'selected="selected"'; }
			  $html .= "<option value='".$item->$value_field."' $selected>".$item->$show_field."</option>\n";
			}
			$html .= "</select>";
			return $html;
		} 

		function AinputHTML ($field_name, $value, $field_def, $ie_display_width = 20, $ie_rows = 5, $class = '', $namesuffix = '', $noslctblank = 0)
		{
		  # So what are our possibilities for field_def?

		  $html = '';
		  if (strtolower($field_def) == 'text')
		  {
		    # Do a text area box thing
		    $html = $this->Ahtmltextarea($field_name . $namesuffix, $value, $class, $ie_display_width, $ie_rows, 'default');
		  }
		  elseif (preg_match("/datetime/i",$field_def))
		  {
		    $html = $this->AhtmlInput($field_name . $namesuffix, $value, $class, 20, 19, ((preg_match("/password/i", $field_name)) ? 1 : 0), '');
		  }
		  elseif (preg_match("/date/i",$field_def))
		  {
		    $html = $this->AhtmlInput($field_name . $namesuffix, $value, $class, 11, 10, ((preg_match("/password/i", $field_name)) ? 1 : 0), '');
		  }
		  elseif (preg_match("/int\((.+)\)/", $field_def, $matches))
		  {
		    $fieldwidth = $matches[1];
		    $html = $this->AhtmlInput($field_name . $namesuffix, $value, $class, $fieldwidth + 1, $fieldwidth, ((preg_match("/password/i", $field_name)) ? 1 : 0), '');
		  }
		  elseif (preg_match("/decimal\((\d+),(\d+)\)/", $field_def, $matches))
		  {
		    $fieldwidth = $matches[1] + $matches[2] + 1;
		    $html = $this->AhtmlInput($field_name . $namesuffix, $value, $class, $fieldwidth + 1, $fieldwidth, ((preg_match("/password/i", $field_name)) ? 1 : 0), '');
		  }
		  elseif (preg_match("/char\((.+)\)/", $field_def, $matches))
		  {
		    # $1 now contains the max length for us! Do a password field if necessary
		    # If ie_display_width is greater than $1 then use $1 instead!
		    #if ($ie_display_width > $matches[0]) { $ie_display_width = $matches[0]; }
		    $html = $this->AhtmlInput($field_name . $namesuffix, $value, $class, $ie_display_width, $matches[1], ((preg_match("/password/i", $field_name)) ? 1 : 0), '');
		  }
		  elseif (preg_match("/enum\((.+)\)/", $field_def, $matches))
		  {
		    # It's an ENUM, possible values now in $1
		    $menu_items = explode(",",$matches[1]);
			$stripped_menu_items = array();
			foreach ($menu_items as $item) 
			{ 
			  $item = preg_replace("/'/", "", $item);
			  array_push($stripped_menu_items, $item); 
			}
			
			$top_item_name  = ' ';
			$top_item_value = '';
			if ($noslctblank)
			{
				$top_item_name = array_shift($stripped_menu_items);
				$top_item_value = $top_item_name;
			}
			# $name, $selected, $top_item_name, $top_item_value, $onchange, $items
		    $html = $this->Ahtmlselect($field_name . $namesuffix, $value, $top_item_name, $top_item_value, "", $stripped_menu_items);
		  }
		  elseif (preg_match("/set\((.+)\)/", $field_def, $matches))
		  {
		    # It's a set, do a multi-select box
			print "TODO!";
			$html = "TODO!";
		  }
		  elseif (preg_match("/blob/", $field_def))
		  {
		    # Let's assume it's an image field
			print "TODO!";
			$html = "TODO!";	
		    #$html = AethSubs::filefieldHTML($field_name, $value, $class);
		  }
		  else
		  {
		    # Default, do a standard text box, unless the field is called password!
		    $html = $this->AhtmlInput($field_name . $namesuffix, $value, $class, $ie_display_width, '', ((preg_match("/password/i", $field_name)) ? 1 : 0), '');
		  }

		  return $html;
		}

		function AhtmlInput ($name, $value, $class = '', $iewidth='', $maxlength='', $password = 0, $extrajs = '', $placeholder = '')
		{
		  $type = 'text';
		  if ($password) { $type = 'password'; }
		  return "<input type=\"$type\" name=\"$name\" id=\"$name\" value=\"$value\" size=\"$width\" maxlength=\"$maxlength\" class=\"$class\" placeholder=\"$placeholder\" $extrajs />";
		}

		function Ahtmltextarea ($name, $value='', $class='', $width='', $rows='', $wrap='virtual', $extrajs='')
		{
		  return "<textarea name='$name' cols='$width' rows='$rows' wrap='$wrap' class='$class' $extrajs>$value</textarea>";
		}

		function Ahtmlcheckbox ($name, $checked = 0, $checkedvalue = '', $class = '', $extrajs = '')
		{
		  $checked_string = ($checked) ? "checked" : "";
		  return "<input type='checkbox' name='$name' value='$checkedvalue' $extrajs $checked_string />";
		}

		function Ahtmlselect ($name, $selected, $top_item_name, $top_item_value, $onchange, $items, $class = '', $extrajs = '')
		{
		  $result = '<select name="' . $name . '" onchange="' . $onchange . '" class="'.$class.'" '.$extrajs.'>' . "\n";

		  # Now the top item
			if ($selected == $top_item_value)
			{
			  $result .= '<option value="' . $top_item_value . '" selected="selected">' . $top_item_name . '</option>' . "\n";
			}
			else
			{
			  $result .= '<option value="' . $top_item_value . '">' . $top_item_name . '</option>' . "\n";
			}

		  foreach ($items as $item)
		  {
		    if ($item == $selected)
		    {
		      $result .= '<option value="' . $item . '" selected="selected">' . $item . '</option>' . "\n";
		    }
		    else
		    {
		      $result .= '<option value="' . $item . '">' . $item . '</option>' . "\n";
		    }
		  }

		  $result .= "</select>";

		  return $result;
		}

		function dateValid($value)
		{
			if (preg_match("/(\d+)\D(\d+)\D(\d+)/", $value, $matches))
			{
				return checkdate($matches[2], $matches[1], $matches[3]);
			}
			else
			{
				return false;
			}
		}

	}



?>