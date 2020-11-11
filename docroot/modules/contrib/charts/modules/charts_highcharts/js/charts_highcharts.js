/**
 * @file
 * JavaScript integration between Highcharts and Drupal.
 */
(function ($) {
  'use strict';

  Drupal.behaviors.chartsHighcharts = {
    attach: function (context, settings) {
      $('.charts-highchart', context).once().each(function () {
        if ($(this).attr('data-chart')) {
          let config = $.parseJSON($(this).attr('data-chart'));
          $(this).highcharts(config);
        }
      });
    },
    detach: function (context, settings, trigger) {
      if (trigger === 'unload') {
        let highcharts_in_context = $('.charts-highchart', context).highcharts();
        if (highcharts_in_context) {
          highcharts_in_context.destroy();
        }
      }
    }

  };
}(jQuery));
