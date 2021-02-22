#!/bin/bash
# Helper Script to Display User's Calendar URLs on a CalDav Server
# derived from:
#   https://blog.sleeplessbeastie.eu/2018/06/18/how-to-display-upcoming-events-in-nextcloud-calendar-using-shell-script/
# needs curl and xmlstarlet to be installed

# CalDav server and path
dav_server="https://your.caldavserver.net"
dav_path="/nextcloud/remote.php/dav/"

# Basic auth credentials
username="youruser"
password="yourpass"

# Get URL for the user's principal resource on the server
dav_user_path=$(curl --silent \
                     --request PROPFIND \
                     --header 'Content-Type: text/xml' \
                     --header 'Depth: 0' \
                     --data '<d:propfind xmlns:d="DAV:">
                               <d:prop>
                                 <d:current-user-principal />
                               </d:prop>
                             </d:propfind>' \
                     --user ${username}:${password} \
                     ${dav_server}${dav_path} | \
                xmlstarlet sel -t -v 'd:multistatus/d:response/d:propstat/d:prop/d:current-user-principal/d:href' -n) 

# Get URL that contains calendar collections owned by the user
dav_user_calendar_home_path=$(curl --silent \
                              --request PROPFIND \
                              --header 'Content-Type: text/xml' \
                              --header 'Depth: 0' \
                              --data '<d:propfind xmlns:d="DAV:" xmlns:c="urn:ietf:params:xml:ns:caldav">
                                        <d:prop>
                                          <c:calendar-home-set />
                                        </d:prop>
                                      </d:propfind>' \
                              --user ${username}:${password} \
                              ${dav_server}${dav_user_path} | \
                         xmlstarlet sel -t -v 'd:multistatus/d:response/d:propstat/d:prop/cal:calendar-home-set/d:href' -n) 

# Get calendar paths                                      
dav_user_calendar_paths=$(curl --silent \
            	     --request PROPFIND \
                     --header 'Content-Type: text/xml' \
                     --header 'Depth: 1' \
                     --data '<d:propfind xmlns:d="DAV:" xmlns:cs="http://calendarserver.org/ns/"><d:prop><d:displayname/></d:prop></d:propfind>' \
                     --user ${username}:${password} \
                     ${dav_server}${dav_user_calendar_home_path} | \
		     xmlstarlet sel -t -m 'd:multistatus/d:response' -i  "string-length(d:propstat/d:prop/d:displayname)" -i "d:propstat/d:status='HTTP/1.1 200 OK'" -v "d:href" -n)

echo "dav_user_path: $dav_user_path"             
echo "dav_user_calendar_home_path: $dav_user_calendar_home_path"
echo "dav_user_calendar_paths: $dav_user_calendar_paths"
