/**
 * @file
 * JavaScript integration between Billboard and Drupal.
 */
(function ($) {
  'use strict';

  Drupal.behaviors.chartsBillboard = {
    attach: function (context, settings) {
      $('.charts-billboard', context).once().each(function () {
        if ($(this).attr('data-chart')) {
          let config = $.parseJSON($(this).attr('data-chart'));
          bb.generate(config);
        }
      });
    }
  };
}(jQuery));
