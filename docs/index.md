# Terra

![Terra command line interface](https://pbs.twimg.com/media/CHj2HvyUYAAaivy.png:large)

## About

Terra is a suite of tools for the purpose of quickly and easily standing up web projects with Docker.

It is designed to be as simple as possible for developers, while being powerful enough to use in production at scale.

With Terra, all you care about is your site's code. Stop wasting time setting up environments. Let terra and docker do all the work for you.

## .terra.yml

Your .terra.yml file gives you tons of power.

Check out a [sample .terra.yml file](https://github.com/terra-ops/terra-cli/blob/master/docs/.terra.yml) and put one in your project's source code.

## Community

Please join the chat on Gitter. We want as much feedback as possible!

[![Join the chat at https://gitter.im/terra-ops/terra-cli](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/terra-ops/terra-cli)

## Documentation & Issues

[Read the Docs](http://terra.readthedocs.org/) or help [improve the docs](https://github.com/terra-ops/terra-cli/edit/master/docs/index.md).

[Issues](https://github.com/terra-ops/terra-cli/issues) and [Story Boarding](https://huboard.com/terra-ops/terra-cli/)

## Origin

Terra was created as a proof of concept. Symfony CLI had proven itself worthy with projects like

- https://github.com/pantheon-systems/terminus
- https://github.com/platformsh/platformsh-cli
- https://github.com/ericduran/flo
- https://github.com/jonpugh/director

The creator of Terra, Jon Pugh, is also the creator of [OpenDevShop](http://getdevshop.com). He was brought on as a maintainer of the [Aegir Project](http://aegirproject.org) by Christopher Gervais.

Jon wanted to create a new CLI that could replace the [Provision](http://drupal.org/project/provision.org) project, and service as the backbone of products like Aegir and DevShop.

The goal is to be more than just a simple CLI, more than a great local dev tool, but to also attempt to solve the bigger problems of scaling, testing, deploying, and monitoring web software.

## Purpose

To make having a website as easy as possible throughout it's entire lifetime, from localhost through large scale production, through entirely open source software.

Terra is a human interface for working on, deploying, testing, and scaling web software projects. 

Terra makes it quick and painless to manage your projects and environments.  

Push a button to get a testing infrastructure.

## "Projects"

A "Project" is your website. It is the source code for your project.  Terra knows the git URL and (will) know the available branches and tags.  Terra will help you update your project from it's upstream repository using git.

## "Environment"

The Environment is all of the systems needed to run the source code.

The "EnvironmentFactory" class will be pluggable.  Out of the box it provides a working docker cluster, but we can extend it to work with a "multiple apache vhost" model or with a different container provider.

Users will be able to use terra to control environments hosted by multiple hosting providers, including localhost all through the same interface.

## Community & Collaboration is Key

We wish to make this tool work for everyone.  We wish to get feedback from all parties interested in solving these problems in order to prioritize what to work on.

Please join us in the Issue Queues on GitHub and the chat rooms on gitter.

## Scalable Out of the Box

We wanted to start from scalable.  Terra's purpose is to make scaling a push button affair.

Currently Terra uses Docker and Docker compose to stand up and scale websites.  

This makes it easy to get environments running quickly on hosted servers or on local computers for development.

## Planning

See https://huboard.com/terra-ops/terra-cli/ for the user story board.

I am going to direct planning in an agile way as much as possible. Please post as much feedback as you can in the issue queues.

## Requirements

- **docker** 
- **docker-compose**

## Commands

#### `terra status`
  List all projects on this system.

#### `terra project:add` 
  Add a new project to the system.
  
  Currently only Drupal sites are supported.
  
#### `terra project:remove`
  Remove a project from the system
  
#### `terra environment:add`
  Add an environment for an project.
  
#### `terra environment:remove`
  Remove an environment.

#### `terra environment:enable`
  Runs `docker-compose up` to initiate an environment.
  
#### `terra environment:disable`
  Runs `docker-compose stop` to disable an environment.
  
#### `terra environment:rebuild`
  Looks in your project's .terra.yml file for `rebuild_source: @drushalias`.  Syncs the database, copies the files, then runs the rebuild hooks.
  
#### `terra environment:status`
  Provides status information about an environment, including path and URL.

#### `terra environment:scale`
  Set the number of "project" containers.  This command is a wrapper for `docker compose project=5`.

# Vagrant

There is a Vagrantfile in the repo that can be used to fire up a linux server with Terra installed.

Use the Vagrant plugin "vagrant-hostsupdater" to automatically set your /etc/hosts file for the VM:
  
  ```
  $ vagrant plugin install vagrant-hostsupdater
  ```

## Tweak the Stack

Since the beginning we knew we had to let users tweak the stack.

We also wanted it to just work, out of the box, so we created  "Default" docker-compose stack that you can see here: https://raw.githubusercontent.com/terra-ops/terra-cli/master/docs/docker-compose-example.yml

We allow your project's source code to change its `docker-compose.yml` stack right now:

```.terra.yml

# Specific settings for docker compose go here
docker_compose:

  # Overrides will replace any item in the entire docker-compose array.
  overrides:
    project:
      image: wordpress
      links:
        - storage
      environment:
        WORDPRESS_DB_HOST: database
        WORDPRESS_DB_USER: drupal
        WORDPRESS_DB_NAME: drupal
        WORDPRESS_DB_PASSWORD: drupal
    storage:
      image: mongo
```
This snippet, if put in your project's `.terra.yml` file, will replace the drupal container with  wordpress, and add & link a MongoDB container.

Anything under "docker_compose: overrides:" is merged with the stock stack, which you can see here: https://raw.githubusercontent.com/terra-ops/terra-cli/master/docs/docker-compose-example.yml

## Example Projects

### Drupal 7 Core

https://github.com/terra-ops/example-drupal

### Drupal 7 Makefile

https://github.com/terra-ops/example-drush-make

### Drupal 8

https://github.com/terra-ops/example-drupal8

### Wordpress

Replaces the `terra/drupal` docker image with `wordpress`

https://github.com/terra-ops/example-wordpress

### Scaler

Simply prints the IP address to test scaling.

https://github.com/terra-ops/example-scale

**Symfony**
Terra API is a symfony project.  Use it as an example.
https://github.com/terra-ops/terra-api


## Coding Standards

As a symfony project, we are following PSR-2 Coding Standards.

Use 4 spaces for indentation, and follow all the other rules specified at http://www.php-fig.org/psr/psr-2/

## Example Project: 18F Agile BPA Prototype

NuCivic submitted a working prototype for the 18F Agile BPA.

We used Terra as the recommended method for recreating the site on another server.

See the [instructions](example-setup.md) on setting up http://openfda.nucivic.build on another server using Terra.

## Terra Projects

Each project you run with terra should have a `.terra.yml` file in the root.

To see an example file, see https://github.com/terra-ops/terra-cli/blob/master/docs/.terra.yml

## Terra API

The "Terra API" project serves as a web based interface for Terra. It is built on Drupal 8.

See the [terra-api](https://github.com/terra-ops/terra-api/blob/master/README.md) GitHub repo for more information.

# History

Some of the R&D for Terra happened in a project called "director": https://github.com/jonpugh/director-drupal

Director is now deprecated.

# Comparisons to Kalabox

There is a striking similarity to this project and Kalabox.   

We love Kalabox and the Kalamuna team, but there are a few key differences:

1. Terra is designed for all things: local development, testing, and production.
2. Terra is written in PHP & Symfony: Kalabox is written in Node JS
3. Terra is a proposed platform to power the future of Aegir & Devshop.  We hope to recruit a large community from those tools.
4. Terra currently extends the kalabox/drush container to offer a container to SSH into.  
 
We hope to collaborate with them on as much as possible.
