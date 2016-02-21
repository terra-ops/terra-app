Director
========

Director is a console tool for orchestrating your server & your software.

Coupled with Ansible, Director makes it easy to track and manage large numbers of servers.

More coming soon.

Current Commands
----------------

- status

  Outputs the current servers and apps in the registry.

- server:add

  Adds a server to the registry.

- app:add

  Adds an app to the registry.

- app:update

  Update the apps information.

- app:init

  Clones the app's source code to the desired path.


Next Steps
----------

- role:add

  Adds a role to the registry.
  Adds the role to .playbook.yml

- server:assign

  Assigns a role to a server.
  Adds the server to .inventory

- server:unassign

  Unassigns a role to a server.
  Removes the server from .inventory

- director:direct

  Runs ansible-playbook using current .playbook.yml and .inventory.
  Updates all of the servers.


Notes
=====

Vars files may be included for a server during it's direct run:

Add "vars_files" to the `config/servers.yml` file
 
 
=======
Installation
------------

1. Install PHP CLI.
2. Install Composer: https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx
  ```
  curl -sS https://getcomposer.org/installer | php
  ```
3. Manually install Director:
  For now we recommend installing it manually as we polish it up for release into packagist:

  1. Git clone to your favorite local projects folder:
    ```
    cd ~/Projects
    git clone git@github.com:jonpugh/director.git
    ```
  2. Go into director folder and run `composer intall`.
  3. Either:

    a. Add a symlink from ~/Projects/director/director to /usr/local/bin or /usr/bin.
    b. Add it to your PATH variable 

We are still working on getting director to work out of the box.
 
 These environment variables are needed after you clone this repo and run `composer install`:
 
 PATH=$PATH:/vagrant/director/vendor/bin:/vagrant/director
 
 export PYTHONPATH=/vagrant/director/vendor/jonpugh/ansible/lib:
 export ANSIBLE_LIBRARY=/vagrant/director/vendor/jonpugh/ansible/library
 export MANPATH=/vagrant/director/vendor/jonpugh/ansible/docs/man:
 export ANSIBLE_HOSTS=/vagrant/director/inventory
