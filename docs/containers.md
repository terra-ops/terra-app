Terra Containers
================

Terra creates a group of containers for every environment using Docker Compose.

Terra dynamically creates a `docker-compose.yml` file for each environment in the `~/.terra/environments` folder.

The default container arrangement is a proof of concept for a scalable, distributed arrangement of services for a typical website.  

However the plan is to allow each project to override parts or the entire docker-compose configuration in the project source code itself.

Host Container
--------------
Each container host gets a single URL Proxy container.

This allows the host to route requests to environment urls to the correct environment container. 

You must run the URL proxy container and manually setup DNS (for now) to be able to host multiple domains per host.

Run `terra url-proxy:enable` to run the URL proxy container. This is currently required as we don't expose any port at all on the environment containers.  The URL proxy container is the main front-loader for all traffic coming into the host on port 80.

### URL Proxy Container
Container: https://github.com/jwilder/nginx-proxy

This container makes it possible to host multiple environments on a single host. 

You can access the environment by hostname, which is generated as `http://project.environment.hostname`. 

If your project is called `myhomepage` and your enviroment was called `live`, and the name of the host of the containers was called `business.com`, the terra environment will automatically be available at `http://myhomepage.live.business.com`, if the DNS is set to resolve.

The issue to allow production domains is available at https://github.com/terra-ops/terra-cli/issues/18.

Environment containers
------------------
Each environment gets at least one of each of these containers.

### Load
- container: `tutum/haproxy`
- Docker Hub: https://registry.hub.docker.com/u/tutum/haproxy/

This container is the endpoint for each environment. 

The tutum/haproxy image is what makes the `terra environment:scale` command possible.  After the project containers are scaled up, the "load" container is restarted and instantly connected to all of the project containers.

Setting the `VIRTUAL_HOST` environment variable will make it possible to lookup that enviromment by a URL rather than on a random port.

### Project

- Container: `terra/drupal` 
- Docker Hub: https://registry.hub.docker.com/u/terra/drupal/
- Source code: https://github.com/terra-ops/docker-drupal

Project is the scalable NGINX container.  It is linked to the `load` container, so when you add more, they are automatically added to the `load` container's server list.

Using the command `terra environment:scale project environment 5` command will pass through to `docker-compose scale project=5'.  Currently terra is hard coded to scale the project container, but with help, we can implement many different methods of scaling.

The source code for your environments is cloned to the *docker host* and then mounted as a volume to the container.  This makes scaling and updating easy.

### Database
- container: `mariadb`
- Docker Hub: https://registry.hub.docker.com/_/mariadb/
- Source code: https://github.com/docker-library/mariadb/blob/master/10.0/Dockerfile

### Drush
- container: `terra/drush`
- Docker Hub: https://registry.hub.docker.com/u/terra/drush
- Source code: https://github.com/terra-ops/docker-drush

The drush container was modified version of kalabox/drush, designed to run perpetually. The plan is to add an SSH server to that it can serve as the drush remote endpoint for developers to access.
