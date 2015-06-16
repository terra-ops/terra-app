Installation
============

Terra uses Composer.

Global Install
--------------

It's recommended to install Terra globally.

1. Prepare Composer

    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
    ln -s /usr/local/bin/composer /usr/bin/composer

2. Install Terra 0.x:

    composer global require terra/terra-app:dev-master
    cd ~/.composer/vendor/terra/terra-app
    composer install

  @TODO: Figure out how to remove the need to run composer install.

3. Install Docker and docker-compose

  **Install Docker:**

  - Install Directions for all OSes: https://docs.docker.com/installation/
  - Mac OSX: https://docs.docker.com/installation/mac/
  - Ubuntu: https://docs.docker.com/installation/ubuntulinux/

  **Install Docker-Compose**

  - Detailed instructions at https://docs.docker.com/compose/install/

    curl -L https://github.com/docker/compose/releases/download/1.2.0/docker-compose-`uname -s`-`uname -m` > /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose

3. That should be it!

  Try to run `terra` on the command line and you should see the default output.
