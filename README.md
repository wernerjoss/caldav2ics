# caldav2ics
create ical calendar file from caldav calendar

This Project is currently just a standalone PHP script which connects to a CalDav Server and creates a local ical calendar file from the remote calendar.
It is indended to be called automatically, e.g. from a cron job on your web server.
As such, it can automatically update ics files that can be used to show events from your CalDav calendar via calendar plugin for your favourite CMS like Wordpress, Grav or any other.
As a reference, see [wp-caldav2ics](https://wordpress.org/plugins/wp-caldav2ics/) which does basically just the same, integrated in Wordpress with a graphical interface.

As I switched from Wordpress to Grav, I needed the same functionality in the new CMS.
This script is currently my simple solution for the task, running via cron job on my server.
Maybe I'll make a Grav Plugin out of this, in the future.

