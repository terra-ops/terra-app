#!/usr/bin/env bash

set -e

# Save certain environment variables to /etc/apache2/envvars so they are available in Apache2 config.
# To make environment variables available to the site itself, see apache-vhost.conf.
echo "TERRA || Saving to /etc/apache2/envvars from environment variables ..."
echo "export VIRTUAL_HOSTNAME=$VIRTUAL_HOSTNAME" >> /etc/apache2/envvars

echo "TERRA || Launching apache2-foreground ..."
sudo apache2-foreground&

echo "TERRA || Writing drushrc.php ..."
if [ ! -d "$HOME/.drush" ]; then
  mkdir "$HOME/.drush"
fi

echo "TERRA || Writing drushrc.php ..."
echo "<?php \
  \$options['root'] = '/var/www/html'; \
  \$options['uri'] = '$VIRTUAL_HOSTNAME'; \
  " > $HOME/.drush/drushrc.php

echo "TERRA || Following log /var/log/terra ..."
tail -f /var/log/terra
