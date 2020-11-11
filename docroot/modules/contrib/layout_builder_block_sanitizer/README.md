CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This module aims to provide a way to automatically clean up blocks that may have
been deleted, and result in "This block is broken or missing. You may be missing
content or you might need to enable the original module." when you've placed
those blocks on a Layout Builder based display for a node content type.

For example, let's say you have a "Promotion" custom block you've created and
placed in the default template for your "Product" page. Now, you've deleted that
block through the UI. All 100 of your "Product" nodes are now going to display
the "Block is broken" error message. With this module, you should be able to
quickly remedy the situation leveraging the batched cleanup process.

The module will examine a specified node or all nodes, and remove any broken
blocks. It will also examine the default layout, and if any offending blocks are
found, it also removes those.

Currently under development. Open to collaborators and suggestions.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/layout_builder_block_sanitizer

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/layout_builder_block_sanitizer


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Layout Builder Block Sanitizermodule as you would normally
   install a contributed Drupal module.
   Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Layout Builder Block Sanitizer.
    3. Enter a node ID to sanitize non-existent blocks from it. Be sure to clear
       caches if blocks have recently been created.


MAINTAINERS
-----------

 * Tyler Fahey (twfahey) - https://www.drupal.org/u/twfahey
