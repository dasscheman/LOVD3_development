echo "Install and setup apache"
sudo apt-get -qq update > /dev/null
sudo apt-get -qq install -y --force-yes apache2 libapache2-mod-php5 php5-curl php5-intl php5-gd php5-idn php-pear php5-imagick php5-imap php5-mcrypt php5-memcache php5-ming php5-ps php5-pspell php5-recode php5-snmp php5-sqlite php5-tidy php5-xmlrpc php5-xsl php5-mysql nmap
## exim4
## smtp
sudo a2enmod rewrite

## Setting the home directory for localhost.
# convert_selenium_to_phpunit.sh assumes that the files are in a folder with this pattern: */svn/*/trunk
# * can be a folder or nothing.
# This is done because of the local folder structure, which I don't want to change.
##mkdir /home/travis/build/dasscheman/LOVD3_development/svn
##sudo mv /home/travis/build/dasscheman/LOVD3_development/trunk /home/travis/build/dasscheman/LOVD3_development/svn
##sudo sed -i -e "s,/var/www,/home/travis/build/dasscheman,g" /etc/apache2/sites-available/default
sudo sed -i -e "s,/var/www,/home/travis/build/dasscheman/LOVD3_development,g" /etc/apache2/sites-available/default
sudo sed -i -e "s,AllowOverride[ ]None,AllowOverride All,g" /etc/apache2/sites-available/default

sudo /etc/init.d/apache2 restart

# Install and Configure Dovecot
echo 'Provisioning Environment with Dovecot and Test Messages'
if which dovecot > /dev/null; then
    echo 'Dovecot is already installed'
else
    echo 'Installing Dovecot'

    # Install Dovecot.
    # Pass the -y flag to suppress interactive requests.
    sudo apt-get -qq -y install dovecot-imapd dovecot-pop3d

    # Prepare the local.conf for custom values
    sudo touch /etc/dovecot/local.conf

    # Move Maildir to the users home directory.
    # This keeps things consistent across environments.
    sudo echo 'mail_location = maildir:/home/%u/Maildir' >> /etc/dovecot/local.conf

    # Enable plaintext for testing.
    # This is pretty awful for production environments.
    sudo echo 'disable_plaintext_auth = no' >> /etc/dovecot/local.conf

    # Running tests in isolation requires a lot of connections very quickly.
    sudo echo 'mail_max_userip_connections = 10000' >> /etc/dovecot/local.conf

    # Restart Dovecot so it gets it's new settings.
    sudo restart dovecot
fi


nmap localhost -p 25