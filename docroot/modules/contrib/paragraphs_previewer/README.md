# Paragraphs Previewer

Provides a rendered preview of a paragraphs item while on an entity form.

# Features

* Preview the rendered paragraph before saving the entity.
* Previewer can be enabled per field instance.
* Full width window to preview the design.
* Resizable window to preview responsive designs.

# Caveats

* Preview popup uses the front end theme to style the rendered markup.  This
  assumes that all styling is applied to that paragraph's markup and does not
  need any other page context / wrapping markup, example node markup.

# Installation

* Install "Paragraphs Previewer" module per https://www.drupal.org/node/1897420.
* Create / Edit a paragraphs field:
  * Set widget to "Paragraphs Previewer"
  * Set "Default edit mode" to "Closed" or "Preview".

# Requirements

* Paragraphs module (https://www.drupal.org/project/paragraphs)
