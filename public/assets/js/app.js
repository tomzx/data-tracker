$(function() {
	$('select').select2({

	});

	$(document).ready(function() {
		$('table').DataTable();
	} );

	Number.prototype.round = function(places) {
		return +(Math.round(this + "e+" + places) + "e-" + places);
	};

	Highcharts.setOptions({
		global: {
			timezoneOffset: (new Date().getTimezoneOffset())
		}
	});

	var graphHandlers = {};

	graphHandlers['all'] = function(chartOptions) {
		var chart = $('.all');
		var defaults = {
			title: {
				text: 'Data log',
			},
			legend: {
				layout: 'vertical',
				align: 'right',
				verticalAlign: 'middle',
				borderWidth: 0
			},
			credits: {
				enabled: false
			},
		};

		chartOptions = $.extend(true, defaults, chartOptions);

		updateChart(chart, chartOptions);
	};

	var updateChart = function(chart, data) {
		if(chart !== null && chart.highcharts()) {
			var highcharts = chart.highcharts();
			for(var i = 0; i < data.series.length; i++) {
				// TODO(tom@tomrochette.com): This still can break if new series are added while looking at the chart
				highcharts.series[i].setData(data.series[i].data, false, false);
			}
			highcharts.xAxis[0].setCategories(data.series.categories, true, true);
		} else {
			chart.highcharts(data);
		}
	};

	var refreshGraphs = function() {
		$('[data-url]').each(function() {
			var $this = $(this),
				url = $this.data('url');

			$.ajax({
				url: url,
				data: {
					from: $('#from').val(),
					to: $('#to').val(),
					format: $('#format').val(),
					normalize: $('#normalize').val(),
				}
			}).done(function(data) {
				if (typeof graphHandlers[url] !== 'undefined') {
					graphHandlers[url](data);
				}
			});
		});
	};

	refreshGraphs();

	$('#from, #to, #format').change(function() {
		refreshGraphs();
	});
});
