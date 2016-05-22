Terra Development
=================

Docker Container Development
----------------------------

In order to improve the docker containers, you must clone the docker container repos and learn the "docker build" command.

Example: 

1. Visit https://github.com/terra-ops/docker-drupal.  Click the "Fork" button to create your own copy of the repo.
2. Clone your repo:  

        git clone https://github.com/MY-USERNAME/docker-drupal

3. Edit the Dockerfile as you see fit.
4. To get the changes to be available in containers on your system, run the docker build command:

        cd docker-drupal
        docker build -t terra/drupal .

  Docker will attempt to rebuild the container based on your Dockerfile.  If something fails you will have to edit the Dockerfile and try again.
  
  The `-t` option indicates the name.  Use the name from the Docker hub.
  the `.` indicates the path to the Dockerfile you are going to build.
  
  When you do this, all new containers built from the `terra/drupal` image on your system will use your newly built image.
  
  If you need to get rid of your customer terra/drupal image and start over, use `docker rmi terra/drupal` to get rid of it. 
  
  A fresh `terra/drupal` will download next time you enable your environment.
  
5. Destroy any running containers you have for that image:

        docker kill myappenvironment_project_1
        
6. Re-enable your environment and it will use the new terra/drupal image:

        terra environment:enable myapp environment

Please file any issues if you have questions! 
