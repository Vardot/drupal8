/**
 * @file
 * JavaScript integration between Chart.js and Drupal.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.chartsChartjs = {
    attach: function (context, settings) {

      $('.charts-chartjs').each(function (param) {
        // Store attributes before switching div for canvas element.
        var chartId = $(this).attr('id');
        var dataChart = "data-chart='" + document.getElementById(chartId).getAttribute("data-chart") + "'";
        var style = 'style="' + document.getElementById(chartId).getAttribute('style') + '"';

        $(this).replaceWith(function (n) {
          return '<canvas ' + dataChart + style + 'id="' + chartId + '"' + '>' + n + '</canvas>'
        });

        $('#' + chartId).once().each(function () {
          var chartjsChart = $(this).attr('data-chart');
          var chart = JSON.parse(chartjsChart);
          var options = chart['options'];
          var myChart = new Chart(chartId, {
            type: chart['type'],
            data: chart['data'],
            options: options
          });

        });

      });

    }
  };

}(jQuery));
