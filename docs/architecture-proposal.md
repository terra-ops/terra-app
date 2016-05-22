Proposed Architecture
=====================

The vision for Terra is big, because our problems are big.

Hopefully this readme will help us keep it all in view.

Most of this hasn't been built yet.

Terra Command Line Interface
----------------------------

This interface will allow you to create, alter, and remove all objects from an inventory:

- Machines
- Projects
- Environments
- Users

Inventories
-----------

When using the terra CLI, it accesses an "inventory" of machines and environments.

An inventory is used as the ansible inventory when managing servers.

An inventory is either local or remote.  A set of YML files or a URL endpoint that provides 
an ansible-compatible "dynamic inventory" from a terra server.

Dynamic Inventory
-----------------

A Terra "Dynamic Inventory" is a webservice that provides an ansible-compatible JSON endpoint.

Users can authenticate against the webservice to be given a limited list of machines, projects and environments, based 
on the desired access control.

Terra Drupal Modules
--------------------

Terra will have a set of decoupled Drupal modules that you can use to build other platforms.

Proof of concepts: https://github.com/terra-ops/terra_ui_module

### terra_machines.module

- Stores machines as entities with fields for things like IP addresses, provider, and roles.
- Account Management: Usernames, passwords, emails, SSH keys.
- User reference access: Easily choose who can access which machines and environments.

### terra_apps.module

- Allows users to create projects and environments.

### terra_inventory.module

- Provides a dynamic inventory webservice for ansible.
