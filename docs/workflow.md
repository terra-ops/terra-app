Terra Workflow
==============

This document will describe what is going on in the background with Terra.

This file should be updated as things change.

Hopefully this helps developers that will want to help contribute to Terra.

## Terra's user and commands

This is about the "host", the system running terra, docker, etc.

The user running terra commands (which we will call the `terra` user, does not need sudo access and should not run with sudo.

As long as that user is in the `docker` group, it can run `docker` without sudo.

The user running terra commands should also be able to run `drush`.

### `terra app:add`

This command writes your "apps" (a website's source code) to the terra config file at `~/.terra/terra`.  

The first time it runs it will save some global config parameters there as well, like your default host.

Nothing else is done at this point. You must create an environment to run your app.

### `terra environment:add`

This command does a few things:

1. Clones the app repo into the chosen path. Defaults to `~/Apps/$APP/$ENVIRONMENT`.
2. Loads up a `.terra.yml` file if there is one. If there are "build" hooks, it runs those.  You cannot use an $alias because the site hasn't been built yet, but you can run things like `drush make` or `composer install`
3. Generates a `docker-compose.yml` file based on information from terra and the apps '.terra.yml' file, for example:
  - uses `document_root` from .terra.yml to construct the path to the web 
  - uses 'docker_compose' to specifiy additions or modifications to the generated docker-compose file.
4. Creates a `docker-compose.yml` file in `~/.terra/environments/$APP/$APP_$ENVIRONMENT/`  
   The reason for the repeat of $APP is that `docker-compose` uses the folder name to name the created containers.

   The `docker-compose.yml` is currently generated with PHP in `EnvironmentFactory::getDockerComposeArray()`.  This is currently hard coded to use the `terra` docker containers (https://registry.hub.docker.com/u/terra/drupal/), but the plan is to make pluggable "DockerStack" classes that change the docker-compose arrangement. 
   
  The `docker-compose.yml` can already be overridden with data from the site's config.  See https://github.com/terra-ops/terra-app/blob/master/docs/.terra.yml and the `EnvironmentFactory

Then, it asks if you wish to enable it.

### `terra environment:enable`

5. The `EnvironmentFactory->enable()` method runs `docker-compose up` in the `~/.terra/environments/$APP/$APP_$ENVIRONMENT/` folder.
6. The first time it will pull the images from docker hub. This takes a few minutes.
7. Then you should see ...

```
DOCKER > Creating drupalanonymous_database_1...
DOCKER > Creating drupalanonymous_app_1...
DOCKER > Creating drupalanonymous_load_1...
DOCKER > Creating drupalanonymous_drush_1...
Environment enabled!  Available at http://drupal.anonymous.tesla and http://localhost:32780
Drush alias file created at /home/jon/.drush/drupal.aliases.drushrc.php
Wrote drush alias file to /home/jon/.drush/drupal.aliases.drushrc.php
Use drush @drupal.anonymous to access the site.

Running ENABLE app hook...
 drush @drupal.anonymous site-install -y
drush @drupal.anonymous uli
```

See `Running ENABLE app hook...`? That is from .terra.yml of the app itself:

This is what the apps .terra.yml file looks like:

```
drush {alias} site-install -y
drush {alias} uli
```



