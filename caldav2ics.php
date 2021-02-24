#!/usr/bin/env  php
<?php
	// Standalone CalDav2ics (e.g. for cron job...)
	// stores Logfile and Calendar File in same Directory as script
	// can fetch/process multiple calendars at once, just put these as a json encoded array in $CalendarsFile, a sample File is included here for convenience
	// as stated below, $CalendarsFile is a json file, but renamed for security reasons, as it contains sensible login data (userames/passwords)
	// proposal is caldav2ics.yaml, as yaml files are usually not served by apache, even if their name/address ist known. but e.g. config.php will also do.
	// the reason I did not use yaml format here is, that most hosting environments do not include php-yaml, but php-json.
	
	// Config goes here:
	$verbose = true;
	$LogEnabled = false;
	$LogFile = pathinfo(__FILE__, PATHINFO_DIRNAME)."/logfile.txt";
	$CalendarsFile = pathinfo(__FILE__, PATHINFO_DIRNAME)."/caldav2ics.yaml";	// config file name, json format with fake extension .yaml for security reasons !
	// end Config
	
	if ( file_exists($CalendarsFile) ) {
		$jsondata = file_get_contents($CalendarsFile);
		$Config = (array) json_decode($jsondata);
	}
	
	foreach ($Config as $entry) {
		if ($verbose)	var_dump($entry);
		foreach ($entry as $calendars) {
			//	var_dump($cal);	//[0][0][0]);
			foreach ($calendars as $calendar) {
				//	var_dump($calendar);
				$name = $calendar[0]->name;
				$calendar_url = $calendar[1]->url;
				$calendar_user = $calendar[2]->user;
				$calendar_password = $calendar[3]->password;
				$ICalFile = pathinfo(__FILE__, PATHINFO_DIRNAME)."/".$name.".ics";	// ical file name
				if ($verbose) {
					echo "\n";
					echo "$name\n";
					echo "$calendar_url\n";
					echo "$calendar_user\n";
					echo "$calendar_password\n";
					echo "$ICalFile\n";
				}
				//	break;
				$fmdelay = 60;	// seconds
				
				if ($LogEnabled)	{
					$loghandle = fopen($LogFile, 'w') or die('Cannot open file:  '.$LogFile);
				}
				if (empty($calendar_url) || empty($calendar_user) || empty($calendar_password))	{
					if (!$LogEnabled) {
						$loghandle = fopen($LogFile, 'w') or die('Cannot open file:  '.$LogFile);
					}
					fwrite($loghandle, "Invalid Settings !\n");
					fwrite($loghandle, "Calendar URL: ".$calendar_url." must be specified\n");
					fwrite($loghandle, "Username: ".$calendar_user." must be specified\n");
					fwrite($loghandle, "Password: ".$calendar_password." must be specified\n");
					fclose($loghandle);
					return;
				}
				
				if (filter_var($calendar_url, FILTER_VALIDATE_URL) === false) {
					print_r("Invalid Calendar URL: ", $calendar_url);
					if (!$LogEnabled) {
						$loghandle = fopen($LogFile, 'w') or die('Cannot open file:  '.$LogFile);
						fwrite($loghandle, "Invalid Calendar URL: ", $calendar_url);
						fclose($loghandle);
					}
					return;
				}
	
				if ($LogEnabled)	{
					print_r($calendar_url);
					fwrite($loghandle, $calendar_url."\n");
					fwrite($loghandle, $calendar_user."\n");
					fwrite($loghandle, $calendar_password."\n");
					fwrite($loghandle, "Delay:".$fmdelay."\n");
					fwrite($loghandle, "EnableLog:".$LogEnabled."\n");
				}	
				// Simple caching system, feel free to change the delay
				if (file_exists($ICalFile)) {
					$last_update = filemtime($ICalFile);
				} else {
					$last_update = 0;
				}
				if ($last_update + $fmdelay < time()) {	
	
					// Get events
					$headers = array(
						'Content-Type: application/xml; charset=utf-8',
						'Depth: 1',
						'Prefer: return-minimal'
					);
					
					// see https://uname.pingveno.net/blog/index.php/post/2016/07/30/Sample-public-calendar-for-ownCloud-using-ICS-parser
					// Prepare request body, MANDATORY !
					$doc  = new DOMDocument('1.0', 'utf-8');
					$doc->formatOutput = true;
	
					$query = $doc->createElement('c:calendar-query');
					$query->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:c', 'urn:ietf:params:xml:ns:caldav');
					$query->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:d', 'DAV:');
	
					$prop = $doc->createElement('d:prop');
					$prop->appendChild($doc->createElement('d:getetag'));
					$prop->appendChild($doc->createElement('c:calendar-data'));
					$query->appendChild($prop);
	
					$prop = $doc->createElement('c:filter');
					$filter = $doc->createElement('c:comp-filter');
					$filter->setAttribute('name', 'VCALENDAR');
					$prop->appendChild($filter);
					$query->appendChild($prop);
	
					$doc->appendChild($query);
					$body = $doc->saveXML();
					
					// Debugging purpose
					if ($LogEnabled) { 
						echo htmlspecialchars($body);
						fwrite($loghandle, htmlspecialchars($body));
					}
	
					// Prepare cURL request
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $calendar_url);
					curl_setopt($ch, CURLOPT_USERPWD, $calendar_user . ':' . $calendar_password);
					curl_setopt($ch, CURLOPT_VERBOSE, 1);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'REPORT');
					curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
	
					$response = curl_exec($ch);
					if (curl_error($ch)) {
						if ($LogEnabled) { 
							echo curl_error($ch); 
							fwrite($loghandle, curl_error($ch));
							fclose($loghandle);
						}
						return;
					}
					curl_close($ch);
	
					// Debugging purpose
					if ($LogEnabled) { 
						echo htmlspecialchars($response);
						fwrite($loghandle, htmlspecialchars($response));
					}
	
					// Get the useful part of the response
					/*	skip any xml conversion/parsing
					*/
	
					// Parse events
					$calendar_events = array();
					$handle = fopen($ICalFile, 'w') or die('Cannot open file:  '.$ICalFile);
					
					// create valid ICS File with only ONE Vcalendar !
					// write VCALENDAR header
					fwrite($handle, 'BEGIN:VCALENDAR'."\r\n");
					fwrite($handle, 'VERSION:2.0'."\r\n");
					fwrite($handle, 'PRODID:-//hoernerfranzracing/caldav2ics.php'."\r\n");
					// find and write TIMEZONE data, new feature, 27.12.19
					$skip = true;
					$wroteTZ = false;
					$lines = explode("\n", $response);
					foreach ($lines as $line)   {
						$line = trim($line);
						if ( !$wroteTZ )	{
							if (startswith($line,'BEGIN:VTIMEZONE'))	{
								$skip = false;
							}
							if ( !$skip )	{
								fwrite($handle, $line."\r\n"); // write everything between 'BEGIN:VTIMEZONE' and 'END:VTIMEZONE'
								// echo $line."\n";
							}
							if (startswith($line,'END:VTIMEZONE'))	{
								$skip = true;
								$wroteTZ = true;    // only write VTIMEZONE entry once
							}
						}
					}
					// parse $response, do NOT write VCALENDAR header for each one, just the event data
					foreach ($lines as $line) {
						$line = trim($line);
						if (strstr($line,'BEGIN:VCALENDAR'))	{	// first occurrence might not be at line start
							$skip = true;
						}
						if (startswith($line,'PRODID:'))	{
							$skip = true;
						}
						if (strstr($line,'VERSION:'))	{
							$skip = true;	// VERSION can appear in different places
						}
						if (startswith($line,'CALSCALE:'))	{
							$skip = true;
						}
						if (startswith($line,'BEGIN:VEVENT'))	{
							$skip = false;
							//fwrite($handle, "\r\n");	// improves readability, but triggers warning in validator :)
						}
						if (startswith($line,'END:VCALENDAR'))	{
							$skip = true;
						}
						if ( !$skip )	{
							fwrite($handle, $line."\r\n");
						}
					}
					fwrite($handle, 'END:VCALENDAR'."\r\n");
					fclose($handle);
						if ($LogEnabled) { 
						fclose($loghandle);
					}
				}
			}
		}
	}
		
	function startswith ($string, $stringToSearchFor) {
		if (substr(trim($string),0,strlen($stringToSearchFor)) == $stringToSearchFor) {
				// the string starts with the string you're looking for
				return true;
		} else {
				// the string does NOT start with the string you're looking for
				return false;
		}
	}
?>
