#!/bin/sh
set -ex

if [ ! `id -u drush` ]; then
    # Create app user
    addgroup --gid $HOST_GID drush

    echo $HOST_UID
    echo $HOST_GID

    adduser --uid $HOST_UID --gid $HOST_GID --system --shell /bin/bash --disabled-password --home /home/drush drush

    mkdir /home/drush/.ssh
    mkdir /home/drush/.drush

    echo $AUTHORIZED_KEYS > /home/drush/.ssh/authorized_keys

    chown drush:drush /home/drush/.ssh -R
    chown drush:drush /home/drush/.drush -R

    ln -s /var/www/html /home/drush/html
    ln -s /app /home/drush/app
fi


/usr/sbin/sshd -D