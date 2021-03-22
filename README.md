# caldav2ics
create ical calendar file from caldav calendar

This Project is currently just a standalone PHP script which connects to a CalDav Server and creates a local ical calendar file from the remote calendar.
It is indended to be called automatically, e.g. from a cron job on your web server.
As such, it can automatically update ics files that can be used to show events from your CalDav calendar via calendar plugin for your favourite CMS like Wordpress, Grav - here you can use the [Grav Scheduler](https://learn.getgrav.org/17/advanced/scheduler) - or any other.
As a reference, see [wp-caldav2ics](https://wordpress.org/plugins/wp-caldav2ics/) which basically just does the same, integrated in Wordpress with a graphical interface.

As I switched from Wordpress to Grav, I needed the same functionality in the new CMS.
This script is currently my simple solution for the task, running via Grav Scheduler on my server.  

The main file is caldav2ics.php, the other 2 are helper scripts: debug.php for offline Analysis of the log file, caldavexplore.sh is used to find out the correct Calendar URL's (which does not always seem to be that easy...)

## Dependencies
- php-curl

## Usage
As of 03/2021 (v 1.1.0), caldav2ics.php reads the Configuration (URL's, username, password..) from a yaml file: caldav2ics.yaml.  
This makes it easy to fetch/process multiple remote Calendars in ONE run.  
Note that this is now real yaml Format, not 'fake', but internally json as before (up to v 1.0.x). The reason for this is security: yaml Files are usually not served by apache, even if anyone knows the exact address/location.
The reason for using json Format before is, that most hosting environments have php-json enabled, but not php-yaml.  
Now, the new Version comes with symfony/yaml included, this way it can also run on servers that do not have php-yaml installed.
For anyone who does not need multiple calendars functionality and does not like editing json config files, there is still the branch 'simple' kept here for convenience.  
Or you can just hardcode the Parameters directly in the source, as before, but in correct php Array notation.

