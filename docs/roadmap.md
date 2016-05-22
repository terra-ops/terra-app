Terra Roadmap
=============

I wish terra to be owned by everyone, and work for every use case.

Pluggable Architecture
----------------------

Terra does not have to rely on Docker containers to run sites.  

Terra is just an interface for managing websites and their environments.

If we figure out a way to create classes that extend the `EnvironmentFactory` class so that an "Environment" can really be anything, then Terra will be useful for sites hosted on any provider, anywhere.

My hope is to make Terra pluggable so we can have things like `DockerEnvironmentFactory` and an `ApacheEnvironmentFactory` that creates environments by creating apache VHost files and databases (like aegir).

An `AcquiaEnvironmentFactory` would be able to track and control environments hosted in Acquia Cloud hosting, and a `PantheonEnvironmentFactory` would be able to do the same.

Future Commands
---------------

- `terra environment:scale`  *Complete*

  This command will scale an environment to an arbitrary number, at the moment. It currently uses `docker-compose scale` to create additional "project" containers.  It automatically restarts the `load` container so that it includes the new containers in it's server list.

  This command would only be possible with the `DockerEnvironmentFactory`, and would have to take on other forms for any other environment provider.

- `terra environment:test`

  This command will run any available suite of tests on the environment.  This should include (but not be limited by) PHPUnit tests, PHP Syntax checking, Code style checks, behat tests, and even load tests.


