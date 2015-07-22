#!/bin/bash
echo "Start Testing"
##sudo supervisorctl reload
gnome-terminal -e "java -jar /usr/share/selenium/selenium-server-standalone.jar -trustAllSSLCertificates" & sleep 2s
PID=`ps -ef |grep selenium-server | grep -v grep | awk '{print $2}'`
# check if selenium server is started. PID (processID) is later used to kill the selenium-server
if [ -z "$PID" ]; then
    echo "Selenium Server is not started!"
    echo "Test is aborted"
    exit
else
    echo "Finished setup, Selenium Server is started"
fi

##wget --retry-connrefused --tries=120 --waitretry=3 --output-file=/dev/null "$SELENIUM_HUB_URL/wd/hub/status" -O /dev/null
##if [ ! $? -eq 0 ]; then
##    echo "Selenium Server not started"
##else
##    echo "Finished setup"
##fi