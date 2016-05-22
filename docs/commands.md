# Common Terra and Docker Commands

```
# Get a list of terra commands
terra

# Create Terra project
terra project:add drupal https://github.com/terra-ops/example-drupal

# Create Terra environment
terra environment:add drupal local
# or
terra e:a drupal local

# Enable Terra environment
terra environment:enable drupal local
# or
terra e:e drupal local

# Drush alias will be created:
drush @drupal.local uli

# Enable subdomain routing (drupal.local.example.com)
terra url-proxy:enable

# Rebuild environment from another drupal site via drush alias

# To rebuild from the source alias defined in your project's .terra.yml file:
#
# .terra.yml:
# |  rebuild_source: @source_alias
#
terra environment:rebuild

# To rebuild from any source alias
terra environment:rebuild -s @source_alias

# Run a drush command inside the drush container
terra environment:drush PROJECT ENVIRONMENT status
terra environment:drush PROJECT ENVIRONMENT uli

# Run an arbitrary command inside any container.  
# Services are from docker composer: load, project, database, drush.
terra environment:run PROJECT ENVIRONMENT SERVICE ping google.com

# View logfiles
docker logs drupallocal_project_1

# Show docker containers
docker ps

# SSH in to docker container
docker exec -it drupallocal_project_1 bash

# Kill docker container
docker kill [name]

# Clean up docker files
# From https://meta.discourse.org/t/low-on-disk-space-cleaning-up-old-docker-containers/15792
docker rm `docker ps -a | grep Exited | awk '{print $1 }'`
docker rmi `docker images -aq`


```
