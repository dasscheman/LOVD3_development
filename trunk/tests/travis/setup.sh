#!/bin/bash

sudo apt-get update

if [ ! -f "/usr/local/bin/composer" ]; then
    echo "Installing Composer"
    php -r "readfile('https://getcomposer.org/installer');" | sudo php -d apc.enable_cli=0 -- --install-dir=/usr/local/bin --filename=composer
else
    echo "Updating Composer"
    sudo /usr/local/bin/composer self-update
fi

if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
    echo "Installing dependencies"
    composer install --dev
fi

echo "Installing nano"
sudo apt-get install nano

echo "Installing Xvfb"
sudo apt-get install xvfb
Xvfb :99 -ac -screen 0 1280x1024x24 export DISPLAY=:99

echo "Installing supervisord"
sudo apt-get install supervisor -y --no-install-recommends
##sudo cp ./trunk/tests/travis/phpunit-environment.conf /etc/supervisor/conf.d/
##sudo sed -i "s/^directory=.*webserver$/directory=${ESCAPED_BUILD_DIR}\\/selenium-1-tests/" /etc/supervisor/conf.d/phpunit-environment.conf
##sudo sed -i "s/^autostart=.*selenium$/autostart=true/" /etc/supervisor/conf.d/phpunit-environment.conf
##sudo sed -i "s/^autostart=.*python-webserver$/autostart=true/" /etc/supervisor/conf.d/phpunit-environment.conf

gnome-terminal -e "java -jar /usr/share/selenium/selenium-server-standalone.jar -trustAllSSLCertificates" & sleep 2s

sudo sed -i -e "s,/var/www,/home/travis/build/dasscheman/LOVD3_development,g" /etc/apache2/sites-available/default
sudo sed -i -e "s,AllowOverride[ ]None,AllowOverride All,g" /etc/apache2/sites-available/default

sudo /etc/init.d/apache2 restart

echo "Installing Firefox"
sudo apt-get install firefox -y --no-install-recommends
firefox -v

if [ ! -f "$SELENIUM_JAR" ]; then
    echo "Downloading Selenium"
    sudo mkdir -p $(dirname "$SELENIUM_JAR")
    sudo wget -nv -O "$SELENIUM_JAR" "$SELENIUM_DOWNLOAD_URL"
fi