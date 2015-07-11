# Update

## Prerequisites

See at [install.md](docs/install.md#prerequisites) for prerequisites.

### Automatic Update

We have created an `update.sh` script that runs you through this entire process.

To run the automatic updater, run the following commands as **root**:

        wget https://raw.githubusercontent.com/terra-ops/terra-app/master/update.sh
        bash update.sh

### Manual Update

Run all of the following commands as root, or with `sudo`.

  To update terra manually, run the following as **root**:
  
        git clone https://github.com/terra-ops/terra-app.git /usr/share/terra
        cd /usr/share/terra
        composer install
        ln -s /usr/share/terra/bin/terra /usr/local/bin/terra
