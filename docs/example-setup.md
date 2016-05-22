Running data-openFDA in Terra
=============================

18F Agile BPA Prototype
-----------------------

We at NuCivic used Terra as the recommended tool for standing up a DKAN site on another server.

This was a part of our working prototype application for the 18F Agile BPA.

To get NuCivic's openFDA DKAN site running in Terra:

1. Install docker: https://docs.docker.com/installation/
2. Install docker-compose: https://docs.docker.com/compose/install/
3. Install terra: http://terra.readthedocs.org/en/latest/install/
4. Install drush: http://docs.drush.org/en/master/install/
4. Add a terra project:

        $ terra project:add openfda https://github.com/NuCivic/data-openFDA.git --description="18F Agile BPA prototype"
         Name:        openopen                                    
         Description: 18F Agile BPA prototype                     
         Repo:        https://github.com/NuCivic/data-openFDA.git 
        Project saved

5. Add a terra environment:

        $ terra environment:add openfda local ~/Projects/openfda-local
        Cloning into '/home/jon/Projects/openfda-local'...
        * master
        On branch master
        Your branch is up-to-date with 'origin/master'.
        nothing to commit, working directory clean
        Environment saved to registry.

6. Enable your terra environment:  
  The first time you do this with terra, it will take some time to download the containers.  Please be patient!
  Make sure to answer "y" to "Write a drush alias file?" so you can access your site.

        $ terra environment:enable openfda local
        DOCKER > Recreating openfdalocal_database_1...
        DOCKER > Recreating openfdalocal_project_1...
        DOCKER > Recreating openfdalocal_load_1...
        DOCKER > Recreating openfdalocal_drush_1...
        Environment enabled!  Available at http://openfda.local.localhost and http://localhost:32786
        Write a drush alias file to /home/jon/.drush/openfda.aliases.drushrc.php ? y
        Drush alias file created at /home/jon/.drush/openfda.aliases.drushrc.php
        Use drush @openfda.local to access the site.

7. Import the SQL database:

        $ drush @openfda.local sqlc < /home/jon/Projects/openfda-local/openfda.sql
        
8. Login to the site:

        $ drush @openfda.local uli
        http://localhost:32786/user/reset/1/1436287649/Nhd4aVeWaUrDW3E2ZXFcPnEAUmf50VHp8ANZjF5wFEU/login

That's it! You should now have a copy of the site running on Docker!
