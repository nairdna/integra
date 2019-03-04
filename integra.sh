#!/usr/bin/env bash
# parameter 1 is the ip address of the storage server
# parameter 2 is the path to the directory
if [[ -z "$1" ]] || [[ -z "$2" ]]; then
  echo "Incomplete parameters, cannot proceed"
  exit 1
else
    echo "Application data will be stored on $1:$2"
    apt install apache2 mysql-client php php-fpm libapache2-mod-php
    a2enmod proxy_fcgi setenvif
    a2enconf php7.2-fpm
    apt install graphviz aspell ghostscript clamav php-pspell php-curl php-gd php-intl php-mysql php-xml php-xmlrpc php-ldap php-zip php-soap php-mbstring
    service apache2 restart

    cp -r integra /var/www/html/
    chmod -R 0755 /var/www/html/Integra-LMS

    mkdir /mnt/moodledata
    mount $1:$2 /mnt/moodledata

    crontab -u www-data -e
    */1 * * * * /usr/bin/php  /var/www/html/Integra-LMS/admin/cli/cron.php >/dev/null
fi