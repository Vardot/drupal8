/**
 * @file
 * JavaScript integration between C3 and Drupal.
 */
(function ($) {
  'use strict';

  Drupal.behaviors.chartsC3 = {
    attach: function (context, settings) {
      $('.charts-c3', context).once().each(function () {
        if ($(this).attr('data-chart')) {
          let config = $.parseJSON($(this).attr('data-chart'));
          c3.generate(config);
        }
      });
    }
  };
}(jQuery));
