Paragraphs Asymmetric Translation Widgets

This module starts as a spin-off from this task:
https://www.drupal.org/project/paragraphs/issues/2461695

This module is still under development and you are advised
to not use it on production environments.

Installation:

TODO (until then, please check the mother issue)

Configuration:

TODO (until then, please chech the mother issue)

Warning:

It is reported that if you change your translation
configuration on an existing site in order to start
using this module, you might experience soft data loss.
More specifically, paragraph entities whose hosting
fields get set from non translatable to translatable
seem to get unlinked from their hosting entities.
This means that they are still in the database (since,
until now, paragraphs module doesn't have garbage
collection routines) but they are not rendered.
TODO: Implement a "migration path".


More details about the module

tldr: The task was about supporting asymmetric translations
on the paragraphs module. After lot of discussion and
effort, the module maintainer suggested that it would
be better if a new module gets created that will offer this
functionality.

This module starts with the latest patch that passed
the tests and was reported to be working.

* In order to use this module, you need to use paragraphs
 module version 1.3 or higher *
