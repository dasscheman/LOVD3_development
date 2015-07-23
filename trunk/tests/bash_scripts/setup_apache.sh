#!/bin/sh

sudo sed -i "/mirror:\\/\\//d" /etc/apt/sources.list
sudo sed -i "1ideb mirror://mirrors.ubuntu.com/mirrors.txt precise main restricted universe multiverse" /etc/apt/sources.list
sudo sed -i "1ideb mirror://mirrors.ubuntu.com/mirrors.txt precise-updates main restricted universe multiverse" /etc/apt/sources.list
sudo sed -i "1ideb mirror://mirrors.ubuntu.com/mirrors.txt precise-backports main restricted universe multiverse" /etc/apt/sources.list
sudo sed -i "1ideb mirror://mirrors.ubuntu.com/mirrors.txt precise-security main restricted universe multiverse" /etc/apt/sources.list

echo "Install and setup apache, xvfb, java and php"
sudo apt-get update -qq
sudo apt-get install -y --force-yes xvfb openjdk-7-jre-headless php5-cli php5-curl php5-xdebug ncurses-term apache2 libapache2-mod-php5 php5-curl php5-intl php5-gd php5-idn php-pear php5-imagick php5-imap php5-mcrypt php5-memcache php5-ming php5-ps php5-pspell php5-recode php5-snmp php5-sqlite php5-tidy php5-xmlrpc php5-xsl --no-install-recommends
sudo apt-get update

sudo a2enmod rewrite

sudo sed -i -e "s,/var/www,/home/travis/build/cioddi/travis-ci-phpunit-selenium-template,g" /etc/apache2/sites-available/default
sudo sed -i -e "s,AllowOverride[ ]None,AllowOverride All,g" /etc/apache2/sites-available/default

sudo /etc/init.d/apache2 restart

echo "Installing Composer"
php -r "readfile('https://getcomposer.org/installer');" | sudo php -d apc.enable_cli=0 -- --install-dir=/usr/local/bin --filename=composer

echo "Updating Composer"
sudo /usr/local/bin/composer self-update

if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
    echo "Installing dependencies"
    composer install --dev
fi
