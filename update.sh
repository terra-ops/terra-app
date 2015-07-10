#!/bin/bash

# Terra Update Script for UBUNTU
# WORK IN PROGRESS!

# See https://github.com/terra-ops/terra-app/blob/master/docs/update.md

# ToDo. Check if Composer, Drush, Docker is already installed

# Update Terra Manually from GitHub
git clone https://github.com/terra-ops/terra-app.git /usr/share/terra
cd /usr/share/terra
composer install
ln -s /usr/share/terra/bin/terra /usr/local/bin/terra

# Notify User
echo "==========================================================="
echo " Terra has been updated! "
echo " "
echo " Thanks! If you have any issues, please submit to https://github.com/terra-ops/terra-app/issues"
echo ""
echo " Now run 'terra' to ensure that it installed correctly."
echo ""
echo "==========================================================="
echo " "
