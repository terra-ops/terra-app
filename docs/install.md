# Installation

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

        wget https://raw.githubusercontent.com/terra-ops/terra-app/master/install.sh
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
    
        composer global require terra/terra-app:dev-master
        echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> $HOME/.bashrc
  
  To install terra manually, run the following as *root*::
  
        git clone https://github.com/terra-ops/terra-app.git /usr/share/terra
        cd /usr/share/terra
        composer install
        ln -s /usr/share/terra/bin/terra /usr/local/bin/terra

7. Generate an SSH key:

  To connect to your drupal sites via drush, your terra user must have an SSH public key.
  
  To generate one:
  
         ssh-keygen -t rsa -N "" -f ~/.ssh/id_rsa
  

7. Switch back to your user and run `terra` to see if it works!
        
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
    
        composer global require terra/terra-app:dev-master
        echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> $HOME/.bashrc
  
  To install terra manually, run the following as *root*::
  
        git clone https://github.com/terra-ops/terra-app.git /usr/share/terra
        cd /usr/share/terra
        composer install
        ln -s /usr/share/terra/bin/terra /usr/local/bin/terra

## Windows

- [https://getcomposer.org/doc/00-intro.md#installation-windows](https://getcomposer.org/doc/00-intro.md#installation-windows)


  _Note: Windows is currently untested._

## Contributing to Terra

**If you plan on contributing to Terra:**

- Fork [https://github.com/terra-ops/terra-app](https://github.com/terra-ops/terra-app)

Then:

    git clone https://github.com/your_username/terra-app.git
    ln -s /path/to/terra-app/terra /usr/local/bin/terra
    cd /path/to/terra-app
    composer install


## That should be it!

Try to run `terra` on the command line and you should see the default output. Remember if you are using Kitematic to use the Docker CLI provided as mentioned above.

![Terra CLI](images/terra_cli.png)

Next you should read up on the [containers strategy](containers.md) or jump right in and try [setting up a sample Drupal installation](drupal.md).
