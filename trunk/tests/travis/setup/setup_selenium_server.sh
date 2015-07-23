serverUrl='http://127.0.0.1:4444'
serverFile=selenium-server-standalone-2.44.0.jar
firefoxUrl=http://ftp.mozilla.org/pub/mozilla.org/firefox/releases/37.0.2/linux-x86_64/en-US/firefox-37.0.2.tar.bz2
firefoxFile=firefox.tar.bz2
phpVersion=`php -v`

sudo apt-get update

##echo "Updating Composer"
##sudo /home/travis/.phpenv/versions/5.3/bin/composer self-update

echo "Installing dependencies"
composer install

echo "Download Firefox"
wget $firefoxUrl -O $firefoxFile
tar xvjf $firefoxFile

echo "Starting xvfb"
echo "Starting Selenium"
if [ ! -f $serverFile ]; then
    wget http://selenium-release.storage.googleapis.com/2.44/$serverFile
fi
if [ ! -e ${serverFile} ]; then
    echo "Cannot find Selenium Server!"
    echo "Test is aborted"
    exit
fi
sudo xvfb-run java -jar $serverFile > /tmp/selenium.log &
wget --retry-connrefused --tries=120 --waitretry=3 --output-file=/dev/null $serverUrl/wd/hub/status -O /dev/null
if [ ! $? -eq 0 ]; then
    echo "Selenium Server not started second attempt"
    # If the selenium server is already running, then the selenium server is not started again.
    javaruns=`ps -ef | grep selenium-server | grep -v grep | wc -l`
    if [ $javaruns = 0 ]; then
        echo "install gnome-terminal"
        sudo apt-get install gnome-terminal
        echo "Start Selenium Server"
        gnome-terminal -e "xvfb-run java -jar ${serverFile} -trustAllSSLCertificates" & sleep 2s
        javaruns=`ps -ef | grep selenium-server | grep -v grep | wc -l`
        if [ $javaruns = 0 ]; then
            echo "Alternatief werkt ook niet"
        else
            echo "Alternatief heeft Selenium Server opgestart"
        fi
    else
        echo "Selenium Server is toch wel running"
    fi
else
    echo "Finished setup"
fi
