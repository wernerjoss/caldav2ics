# caldav2ics
create ical calendar file from caldav calendar

This Project is currently just a standalone PHP script which connects to a CalDav Server and creates a local ical calendar file from the remote calendar.
It is indended to be called automatically, e.g. from a cron job on your web server.
As such, it can automatically update ics files that can be used to show events from your CalDav calendar via calendar plugin for your favourite CMS like Wordpress, Grav - here you can use th [Grav Scheduler](https://learn.getgrav.org/17/advanced/scheduler) - or any other.
As a reference, see [wp-caldav2ics](https://wordpress.org/plugins/wp-caldav2ics/) which does basically just the same, integrated in Wordpress with a graphical interface.

As I switched from Wordpress to Grav, I needed the same functionality in the new CMS.
This script is currently my simple solution for the task, running via Grav Scheduler on my server.  

The main file is caldav2ics.php, the other 2 are helper scripts: debug.php for offline Analysis of the log file, caldavexplore.sh is used to find out the correct Calendar URL's (which does not always seem to be that easy...)

## Dependencies
- php-curl
- php-json

## Usage
This is the Version before 02/2021, with all configuration and functionality in one file: caldav2ics.php.  
It can only handle one calendar, but might be easier to use for some as there is no json config file editing necessary.
it is therefore kept here in this branch for convenience.
