#!/usr/bin/env php
<?php
	// this file is a helper script to be used to debug problems with caldav2ics.php - uses logfiles from that as input
    $logfile = "logfile.txt";
    $ICalFile = "calendar.ics";
	
	// commonly used ics Properties, see https://en.wikipedia.org/wiki/ICalendar
	$Properties = ["DTSTAMP","URL","URL;VALUE=URI","CREATED","UID","LAST-MODIFIED","SUMMARY","LOCATION","DTSTAMP","DTSTART","DTSTART;VALUE=DATE","DTEND","DTEND;VALUE=DATE","TRANSP","DESCRIPTION","UID","CLASS","STATUS","SEQUENCE","RRULE","RDATE","EXDATE","BEGIN","END","TRIGGER","ACTION","CATEGORIES","GEO","ATTENDEE","ROLE","EMAIL","CN"];
	
	if ($handle = fopen($logfile, "r"))   {
        // Get the useful part of the response
        $response = file_get_contents($logfile);
        echo $response;
		
		/*	skip xml processing as a whole :)
		*/

		// Parse events
		$calendar_events = array();
		$handle = fopen($ICalFile, 'w') or die('Cannot open file:  '.$ICalFile);
		
		// create valid ICS File with only ONE Vcalendar !
		// write VCALENDAR header
		fwrite($handle, 'BEGIN:VCALENDAR'."\r\n");
		fwrite($handle, 'VERSION:2.0'."\r\n");
		fwrite($handle, 'PRODID:-//github.com/wernerjoss/caldav2ics'."\r\n");
		// find and write TIMEZONE data, new feature, 27.12.19
		$skip = true;
		$wroteTZ = false;
		$lines = explode("\n", $response);
		foreach ($lines as $line)   {
			$line = trim($line);
			if ($wroteTZ == false)	{
				if (startswith($line,'BEGIN:VTIMEZONE'))	{
					$skip = false;
				}
				if ( !$skip )	{
					fwrite($handle, $line."\r\n"); // write everything between 'BEGIN:VTIMEZONE' and 'END:VTIMEZONE'
					// echo $line."\n";
				}
				if (startswith($line,'END:VTIMEZONE'))	{
					$skip = true;
					$wroteTZ = true;	// write only one VTIMEZONE entry
				}
			}
		}
		// parse $response, do NOT write VCALENDAR header for each one, just the event data
		foreach ($lines as $line) {
			$line = trim($line);
			if (strstr($line,'BEGIN:VCALENDAR'))	{	// first occurrence might not be at start of line !
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
			// more validations 20.09.24
			//if (!str_contains($line, ':')) {	// skip all lines that do not contain ':' (Keyword)
			if (!strpos($line, ':')) {	// TODO: better join lines to previous one :-)
				$skip = true;
			}	else {
				$parts = explode(":",$line);
				$keyword = $parts[0];
				if (in_array($keyword, $Properties))  {
					$skip = false;
				}	else	{
					if (startswith($line,'ORGANIZER;CN=')) {
						$skip = false;
					}	else	{
						$skip = true;
					}
				}
			}
			if ( !$skip )	{
				if ((startswith($line,'URL:')) || (startswith($line,'URL;VALUE=URI:'))) {	// check if 'URL:..' Line contains valid URL
					$url = str_replace("URL:", "", $line);
					$url = str_replace("URL;VALUE=URI:", "", $url);
					$url = trim($url);
					if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
						if ($LogEnabled)	{
							fwrite($loghandle, $url.' is Not a valid URL'."\r\n");
							fwrite($loghandle, "Line: ".$line."\r\n");
						}
					}	else {
						fwrite($handle, $line."\r\n");
					}
				}	else {
					fwrite($handle, $line."\r\n");
				}
			}
		}	
		fwrite($handle, 'END:VCALENDAR'."\r\n");
		fclose($handle);
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
