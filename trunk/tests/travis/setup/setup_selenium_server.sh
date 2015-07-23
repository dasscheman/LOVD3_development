serverUrl='http://127.0.0.1:4444'
serverFile=2.44/selenium-server-standalone-2.44.0.jar
firefoxUrl=http://ftp.mozilla.org/pub/mozilla.org/firefox/releases/37.0.2/linux-x86_64/en-US/firefox-37.0.2.tar.bz2
firefoxFile=firefox.tar.bz2
phpVersion=`php -v`

sudo apt-get update

echo "Installing Composer"
php -r "readfile('https://getcomposer.org/installer');" | sudo php -d apc.enable_cli=0 -- --install-dir=/usr/local/bin --filename=composer

echo "Updating Composer"
sudo ./bin/composer self-update

echo "Installing dependencies"
composer install --dev

echo "Download Firefox"
wget $firefoxUrl -O $firefoxFile
tar xvjf $firefoxFile

echo "Starting xvfb"
echo "Starting Selenium"
if [ ! -f $serverFile ]; then
    wget http://selenium-release.storage.googleapis.com/$serverFile
fi
sudo xvfb-run java -jar $serverFile > /tmp/selenium.log &

wget --retry-connrefused --tries=60 --waitretry=1 --output-file=/dev/null $serverUrl/wd/hub/status -O /dev/null
if [ ! $? -eq 0 ]; then
    echo "Selenium Server not started"
else
    echo "Finished setup"
fi