CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------
Prevent homepage deletion

This module provides a new permission: delete_homepage_node.
Only users with this permission can delete the node that is currently configured
as the home page of the site. Other users will not see the delete-tab, nor the
delete option in the content overview.

Issues
https://www.drupal.org/project/issues/prevent_homepage_deletion

REQUIREMENTS
------------
No

INSTALLATION
------------
- Install with composer
$ composer require 'drupal/prevent_homepage_deletion:^1.0'
- Give someone the permission to delete the homepage, or don't if nobody should
delete it ;-).

CONFIGURATION
-------------
* Configure the user permissions in Administration » People » Permissions

MAINTAINERS
-----------
Current maintainers:
 * Josha Hubbers (JoshaHubbers) - https://www.drupal.org/u/joshahubbers-0
