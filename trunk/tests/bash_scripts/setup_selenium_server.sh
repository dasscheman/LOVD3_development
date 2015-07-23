serverUrl='http://127.0.0.1:4444'
serverFile=selenium-server-standalone-2.44.0.jar
firefoxUrl=http://ftp.mozilla.org/pub/mozilla.org/firefox/releases/37.0.2/linux-x86_64/en-US/firefox-37.0.2.tar.bz2
firefoxFile=firefox.tar.bz2
phpVersion=`php -v`

echo "Download Firefox"
wget $firefoxUrl -O $firefoxFile
tar xvjf $firefoxFile