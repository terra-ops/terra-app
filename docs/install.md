# Installation

## Quick Start

This process is basically the same on Windows, OSX, and Linux.  If running Linux, you can skip virtualbox if you want to run docker locally.

 1. [VirtualBox 5+](https://www.virtualbox.org/wiki/Downloads) and [Docker Toolbox](https://www.docker.com/toolbox)
 2. PHP & Git.
 3. Composer: `curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer` 
 3. Terra: `composer global require terra/cli:dev-master`
 4. Drush: `composer global require drush/drush`
 5. Put `export PATH="$HOME/.composer/vendor/bin:$PATH"` in your `.bash_profile` file to make `terra` and `drush` executable.
 5. Open *Applications >  Docker > Docker Quickstart Terminal* or *Kitematic* (Docker UI). Either way, you will have to first wait for the VM to download and start.
 6. Launch *Docker Quickstart Terminal* or click the *Docker Cli* button in Kitematic to open a terminal.
 7. Type `terra` to make sure it works.  You will see a list of commands.
 8. Type `terra project:add`.  All you need is a git URL with your site. There are no arguments or options required. It will walk you through creating a new environment as well, and then ask if you'd like to enable it.
 9. When you enable your environment the first time, it will take time to download all of the Docker containers.  Please be patient. Once they are downloaded this enabling environments is very fast.
 10. Once the environment enables, Terra will show you the system URL, usually something like http://local.computer:35000.  Click that and you should see your drupal site. (See below for more on local.computer)
 11. Setup your database connection info in settings.php:
  ```php
  $databases['default']['default'] = array(
    'driver' => 'mysql',
    'database' => 'drupal',
    'username' => 'drupal',
    'password' => 'drupal',
    'host' => 'database',
  );
  ```
  It's always the same, for every site.
  
 12. Try the drush alias to ensure the DB is connected and the containers are running
  ```sh
  $ drush @PROJECT.ENVIRONMENT ssh
  $ drush @PROJECT.ENVIRONMENT sqlc
  $ drush @PROJECT.ENVIRONMENT site-install
  ```

That's it! Remember the commands `terra` which will show you the available commands, and `terra status`, which will show you the available projects and environments.

If you have *ANY* problems with these instructions, please submit an issue at https://github.com/terra-ops/terra-cli/issues! 

Thanks!

## Upgrade

Terra is still in pre-release development!

Master branch is generally stable.You can always update your terra install easily thanks to composer:

```
composer global update
```

This command updates all of the composer packages you have installed using `composer global`, including drush.

## About `local.computer`

The domain name [local.computer](http://local.computer) was purchased by @jonpugh for Terra and docker development.

The hostnames [local.computer](http://local.computer) and all subdomains (*.local.computer) resolve to the default docker-machine IP: 192.168.99.100

You can use "local.computer" as the host for your projects if you are using the Docker toolbox.

If you create more docker machines, or change the default one, it might not have the same IP.  Make sure your docker machine always has that IP if you don't want to set your own DNS or load sites via IP.

### Archived, lengthy Instructions 
Currently there are a few steps to get terra working.  

We want installation to be as fast and simple as possible, so we will be working on a single install script that sets up all of the prerequisites for any OS. 

## Prerequisites

Terra depends on the following tools.  The instructions below will guide you through setting up all of them.

- PHP
- git
- Composer [http://getcomposer.org](http://getcomposer.org)
- Drush (7.x) [http://drush.org](http://drush.org)
- Docker (1.7.x) [http://docker.com](http://docker.com)
- Docker Compose (1.3.x) [https://docs.docker.com/compose](https://docs.docker.com/compose)
- Docker Machine (0.3.x) [https://docs.docker.com/machine](https://docs.docker.com/machine)
- VirtualBox (4.3, [Look for "Older Builds" on the website.](https://www.virtualbox.org/wiki/Download_Old_Builds_4_3)) Required only on MacOSx 

Docker Machine isn't currently required but it will be soon. :)

## Ubuntu 

Terra recommends Ubuntu Trusty (14.04 or higher).

### Automatic Install for Ubuntu 14

We have created a `install.sh` script that runs you through this entire process

To run the automatic installer, run the following commands as root:

        wget https://raw.githubusercontent.com/terra-ops/terra-cli/master/install.sh
        bash install.sh

### Manual Install

Run all of the following commands as root, or with `sudo`.

 1. Install PHP & Git:

        apt-get update
        apt-get install php5-cli git

 2. Install Composer:  
  
  From https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx
  
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

  This will install the composer.phar file at /usr/local/bin/composer.
  
 3. Install Docker:

  From https://docs.docker.com/installation/ubuntulinux

        wget -qO- https://get.docker.com/ | sh
        
 4. Add your user to the `docker` group:

        usermod -aG docker your-user

  The docker installer will remind you of this at the end.

 5. Install Docker Compose:

  From https://docs.docker.com/compose/install/
  
        curl -L https://github.com/docker/compose/releases/download/1.2.0/docker-compose-`uname -s`-`uname -m` > /usr/local/bin/docker-compose
        chmod +x /usr/local/bin/docker-compose

 6. Install Drush:

  Drush is used to connect to your running drupal sites.  This step is not required.

  From http://docs.drush.org/en/master/install/
  
        git clone https://github.com/drush-ops/drush.git /usr/share/drush --branch=7.x
        cd /usr/share/drush
        composer install
        ln -s /usr/share/drush/drush /usr/local/bin/drush

 6. Install Terra:

  Terra can be installed with `composer global require` however an extra step is 
  needed to put composer's `bin` folder into your system path.
  
  To install terra automatically, run the following as *your user* (not root):
    
        composer global require terra/cli:dev-master
        echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> $HOME/.bashrc
  
  To install terra manually, run the following as *root*::
  
        git clone https://github.com/terra-ops/terra-cli.git /usr/share/terra
        cd /usr/share/terra
        composer install
        ln -s /usr/share/terra/bin/terra /usr/local/bin/terra

 7. Generate an SSH key:

  To connect to your drupal sites via drush, your terra user must have an SSH public key.
  
  To generate one:
  
         ssh-keygen -t rsa -N "" -f ~/.ssh/id_rsa
  

 8. Switch back to your user and run `terra` to see if it works!
        
## OSX

Running docker and the others natively in OSx works well.

The docker host, or daemon, must run in a virtualmachine.

"boot2docker" is the virtualmachine 

There is a lot of nuance in using Docker on OSX.  It will help to read the guide about Docker on OSX here: http://viget.com/extend/how-to-use-docker-on-os-x-the-missing-guide

 1. Install PHP & Git:

  Install the command line developer tools by installing XCode

 2. Install composer:

  From https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx
  
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

 3. Install docker & docker-compose:

  The easiest way to get up and running on OSX with Docker is to use [Kitematic](http://kitematic.com). Kitematic will handle all prerequisites.

  After installing Kitematic, you're going to want to use the Docker CLI it makes available to execute docker commands:

  ![Kitematic CLI](images/kitematic_cli.png)

 6. Install Terra:

  Terra can be installed with `composer global require` however an extra step is 
  needed to put composer's `bin` folder into your system path.
  
  To install terra automatically, run the following as *your user* (not root):
    
        composer global require terra/cli:dev-master
        echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> $HOME/.bashrc
  
  To install terra manually, run the following as *root*::
  
        git clone https://github.com/terra-ops/terra-cli.git /usr/share/terra
        cd /usr/share/terra
        composer install
        ln -s /usr/share/terra/bin/terra /usr/local/bin/terra

## Windows

- [https://getcomposer.org/doc/00-intro.md#installation-windows](https://getcomposer.org/doc/00-intro.md#installation-windows)


  _Note: Windows is currently untested._

## Contributing to Terra

**If you plan on contributing to Terra:**

- Fork [https://github.com/terra-ops/terra-cli](https://github.com/terra-ops/terra-cli)

Then:

    git clone https://github.com/your_username/terra-cli.git
    ln -s /path/to/terra-cli/bin/terra /usr/local/bin/terra
    cd /path/to/terra-cli
    composer install


## That should be it!

Try to run `terra` on the command line and you should see the default output. Remember if you are using Kitematic to use the Docker CLI provided as mentioned above.

![Terra CLI](images/terra_cli.png)

Next you should read up on the [containers strategy](containers.md) or jump right in and try [setting up a sample Drupal installation](drupal.md).
    
