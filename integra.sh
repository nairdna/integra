#!/usr/bin/env bash
# parameter 1 is the ip address of the storage server
# parameter 2 is the path to the directory
if [[ -z "$1" ]] || [[ -z "$2" ]]; then
  echo "Installing moodledata in local storage"
  path="/var/moodledata"
fi
    echo "Application data will be stored on $1:$2"
    apt -y install apache2 mysql-client php php-fpm libapache2-mod-php
    apt -y install graphviz aspell ghostscript clamav php-pspell php-curl php-gd php-intl php-mysql php-xml php-xmlrpc php-ldap php-zip php-soap php-mbstring
    service apache2 restart

    rm /var/www/html/index.html
    cp -r lms/* /var/www/html/
    chmod -R 0755 /var/www/html

    if [[ -z "$path" ]]; then
      mkdir /mnt/moodledata
      mount $1:$2 /mnt/moodledata
    else
      mkdir $path
      chown -R www-data $path
      chmod -R 777 $path
    fi

    cronjob="*/1 * * * * /usr/bin/php  /var/www/html/admin/cli/cron.php >/dev/null"
    (crontab -u www-data -l; echo "$cronjob" ) | crontab -u www-data -
    echo "Ready to go"
